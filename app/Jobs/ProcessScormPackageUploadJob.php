<?php

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormPackage;
use App\Support\MediaDuration;
use App\Support\MediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ProcessScormPackageUploadJob implements ShouldQueue
{
    use Queueable;

    /** Estrazione zip + upload massivo su S3: può superare i 2 minuti del worker di default. */
    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public string $scormPackageId, public string $tenantId) {}

    public function handle(): void
    {
        $package = ScormPackage::query()->find($this->scormPackageId);
        if (! $package || ! $package->s3_path) {
            Log::warning('SCORM processing skipped: package or s3_path missing', [
                'scorm_package_id' => $this->scormPackageId,
            ]);
            $package?->update(['status' => ProcessingStatus::Error->value]);

            return;
        }

        $disk = MediaStorage::disk();
        $path = $package->s3_path;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            $package->update([
                's3_path' => ltrim($path, '/'),
                'status' => ProcessingStatus::Ready->value,
            ]);

            return;
        }

        $tmpRoot = storage_path('app/tmp/scorm-'.$package->id.'-'.uniqid());
        $zipPath = $tmpRoot.'/package.zip';
        $extractPath = $tmpRoot.'/extracted';
        @mkdir($extractPath, 0777, true);

        try {
            file_put_contents($zipPath, Storage::disk($disk)->get($path));

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \UnexpectedValueException('Cannot open uploaded SCORM zip.');
            }
            $zip->extractTo($extractPath);
            $zip->close();

            $targetBase = "tenants/{$this->tenantId}/scorm/{$package->id}";
            $manifestPath = null;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $absolute = $file->getPathname();
                $relative = str_replace('\\', '/', substr($absolute, strlen($extractPath) + 1));
                $opts = $this->putOptionsForExtractedAsset($disk, $relative);

                Storage::disk($disk)->put(
                    "{$targetBase}/{$relative}",
                    file_get_contents($absolute),
                    $opts,
                );

                if (strtolower(basename($relative)) === 'imsmanifest.xml') {
                    $manifestPath = $relative;
                }
            }

            $launchRelative = $this->resolveLaunchPath($extractPath, $manifestPath);
            $launchKey = "{$targetBase}/{$launchRelative}";

            $durationSeconds = $this->detectDurationSeconds($extractPath, $manifestPath);
            $detectedVersion = $this->detectScormVersion($extractPath, $manifestPath);
            $slideSize = $this->detectSlideSize($extractPath, $launchRelative);

            $manifestMeta = [
                'manifest_path' => $manifestPath,
                'launch_path' => $launchRelative,
                'duration_seconds' => $durationSeconds,
            ];
            if ($slideSize !== null) {
                $manifestMeta['slide_width'] = $slideSize['width'];
                $manifestMeta['slide_height'] = $slideSize['height'];
            }

            $package->update([
                's3_path' => $launchKey,
                'version' => $detectedVersion ?? $package->version,
                'manifest' => $manifestMeta,
                'status' => ProcessingStatus::Ready->value,
            ]);

            if ($durationSeconds !== null) {
                Lesson::query()
                    ->whereKey($package->lesson_id)
                    ->update(['duration_seconds' => $durationSeconds]);
            }
        } catch (\Throwable $e) {
            Log::error('SCORM processing failed', [
                'scorm_package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->update([
                'status' => ProcessingStatus::Error->value,
            ]);
        } finally {
            $this->cleanupDir($tmpRoot);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SCORM processing job failed', [
            'scorm_package_id' => $this->scormPackageId,
            'error' => $exception?->getMessage(),
        ]);

        ScormPackage::query()
            ->whereKey($this->scormPackageId)
            ->where('status', ProcessingStatus::Processing->value)
            ->update(['status' => ProcessingStatus::Error->value]);
    }

    /**
     * Durata del contenuto rilevata automaticamente:
     * 1. dal manifest (LOM typicalLearningTime, se l'authoring tool la esporta);
     * 2. altrimenti via ffprobe sul file video più grande del pacchetto
     *    (copre gli SCORM che incapsulano un semplice mp4).
     */
    private function detectDurationSeconds(string $extractPath, ?string $manifestPath): ?int
    {
        if ($manifestPath !== null) {
            $manifestAbs = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $manifestPath);
            $fromManifest = MediaDuration::scormManifestSeconds($manifestAbs);
            if ($fromManifest !== null) {
                return $fromManifest;
            }
        }

        $mainVideo = $this->findLargestVideoFile($extractPath);

        return $mainVideo !== null ? MediaDuration::probeFileSeconds($mainVideo) : null;
    }

    private function findLargestVideoFile(string $basePath): ?string
    {
        $best = null;
        $bestSize = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['mp4', 'webm', 'm4v', 'mov'], true)) {
                continue;
            }

            $size = (int) $file->getSize();
            if ($size > $bestSize) {
                $bestSize = $size;
                $best = $file->getPathname();
            }
        }

        return $best;
    }

    /**
     * Dimensioni native slide (es. commento iSpring <!-- 1296 864 -->).
     *
     * @return array{width: int, height: int}|null
     */
    private function detectSlideSize(string $extractPath, string $launchRelative): ?array
    {
        $launchAbs = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $launchRelative);
        if (! is_file($launchAbs)) {
            return null;
        }

        $head = (string) file_get_contents($launchAbs, false, null, 0, 4096);
        if (preg_match('/<!--\s*(\d{2,5})\s+(\d{2,5})\s*-->/', $head, $m)) {
            $w = (int) $m[1];
            $h = (int) $m[2];
            if ($w >= 320 && $h >= 240) {
                return ['width' => $w, 'height' => $h];
            }
        }

        return null;
    }

    private function detectScormVersion(string $extractPath, ?string $manifestPath): ?string
    {
        if ($manifestPath === null) {
            return null;
        }

        $manifestAbs = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $manifestPath);
        if (! is_file($manifestAbs)) {
            return null;
        }

        $xml = (string) file_get_contents($manifestAbs);
        if ($xml === '') {
            return null;
        }

        if (preg_match('/2004|cam\s*1\.3|adlcp_v1p3|imsss/i', $xml)) {
            return '2004';
        }

        if (preg_match('/adlcp_rootv1p2|imscp_rootv1p1p2|scorm\s*1\.2/i', $xml)) {
            return '1.2';
        }

        return null;
    }

    private function resolveLaunchPath(string $extractPath, ?string $manifestPath): string
    {
        if ($manifestPath) {
            $manifestAbs = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $manifestPath);
            if (is_file($manifestAbs)) {
                $xml = @simplexml_load_file($manifestAbs);
                if ($xml !== false) {
                    $xml->registerXPathNamespace('adlcp', 'http://www.adlnet.org/xsd/adlcp_rootv1p2');
                    $xml->registerXPathNamespace('imscp', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');
                    $resources = $xml->xpath('//imscp:resource');
                    if ($resources && isset($resources[0])) {
                        $href = (string) ($resources[0]['href'] ?? '');
                        if ($href !== '') {
                            $baseDir = dirname($manifestPath);
                            $baseDir = $baseDir === '.' ? '' : trim(str_replace('\\', '/', $baseDir), '/').'/';

                            return ltrim($baseDir.$href, '/');
                        }
                    }
                }
            }
        }

        foreach (['index_lms.html', 'index.html'] as $candidate) {
            $found = $this->findFirstByName($extractPath, $candidate);
            if ($found) {
                return $found;
            }
        }

        return 'index.html';
    }

    private function findFirstByName(string $basePath, string $filename): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getBasename()) === strtolower($filename)) {
                return str_replace('\\', '/', substr($file->getPathname(), strlen($basePath) + 1));
            }
        }

        return null;
    }

    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $full = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($full)) {
                $this->cleanupDir($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($dir);
    }

    /** @return array<string, mixed> */
    private function putOptionsForExtractedAsset(string $disk, string $relativePath): array
    {
        $opts = MediaStorage::putOptionsForDisk($disk);

        $driver = (string) config("filesystems.disks.{$disk}.driver");
        if ($driver !== 's3') {
            return $opts;
        }

        return array_merge($opts, [
            'ContentType' => $this->guessMimeType($relativePath),
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function guessMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');

        return match ($ext) {
            'html', 'htm' => 'text/html; charset=UTF-8',
            'js', 'mjs' => 'application/javascript; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'xml' => 'application/xml; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            default => 'application/octet-stream',
        };
    }
}
