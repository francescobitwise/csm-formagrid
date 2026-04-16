<?php

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\Tenant\ScormPackage;
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

    public function __construct(public string $scormPackageId, public string $tenantId) {}

    public function handle(): void
    {
        $package = ScormPackage::query()->find($this->scormPackageId);
        if (! $package || ! $package->s3_path) {
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

            $package->update([
                's3_path' => $launchKey,
                'manifest' => [
                    'manifest_path' => $manifestPath,
                    'launch_path' => $launchRelative,
                ],
                'status' => ProcessingStatus::Ready->value,
            ]);
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
