<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Learner;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\ScormPackage;
use App\Support\MediaStorage;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScormContentController extends Controller
{
    public function __invoke(Request $request, string $package, ?string $path = null)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $pkg = ScormPackage::query()
            ->whereKey($package)
            ->first(['id', 'lesson_id', 's3_path']);

        abort_unless($pkg && is_string($pkg->s3_path) && $pkg->s3_path !== '', 404);

        $isEnrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course', function ($q) use ($pkg) {
                $q->whereHas('lessons', fn ($lq) => $lq->whereKey($pkg->lesson_id));
            })
            ->exists();

        abort_unless($isEnrolled, 403);

        $objectKey = $this->resolveObjectKey((string) $pkg->s3_path, (string) $pkg->id, $path);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(MediaStorage::disk());

        return $this->streamAsset($disk, $objectKey);
    }

    private function resolveObjectKey(string $launchS3Path, string $packageId, ?string $path): string
    {
        $launchKey = ltrim($launchS3Path, '/');
        $rootPrefix = $this->packageRootPrefix($launchKey, $packageId);

        $launchRel = str_starts_with($launchKey, $rootPrefix)
            ? substr($launchKey, strlen($rootPrefix))
            : basename($launchKey);

        $requested = $path !== null && $path !== ''
            ? ltrim($path, '/')
            : $launchRel;

        $normalized = $this->normalizePath($requested);
        abort_if($normalized === null, 400);

        return $rootPrefix.$normalized;
    }

    private function packageRootPrefix(string $launchS3Path, string $packageId): string
    {
        // Default: job SCORM estrae in tenants/{tenant}/scorm/{packageId}/...
        $tenantId = (string) (tenant('id') ?? '');
        if ($tenantId !== '') {
            return "tenants/{$tenantId}/scorm/{$packageId}/";
        }

        // Fallback: use directory of launch file.
        $launchKey = ltrim($launchS3Path, '/');
        $dir = trim(str_replace('\\', '/', dirname($launchKey)), '/');

        return $dir !== '' ? ($dir.'/') : '';
    }

    /**
     * Normalizza path relativo dentro il pacchetto (consente ".." ma senza uscire dalla root del pacchetto).
     */
    private function normalizePath(string $path): ?string
    {
        $p = str_replace('\\', '/', trim($path));
        if ($p === '') {
            return null;
        }

        $parts = array_values(array_filter(explode('/', $p), fn ($x) => $x !== ''));
        $out = [];
        foreach ($parts as $seg) {
            if ($seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if (empty($out)) {
                    return null;
                }
                array_pop($out);
                continue;
            }
            $out[] = $seg;
        }

        return implode('/', $out);
    }

    private function streamAsset(FilesystemAdapter $disk, string $objectKey): Response|StreamedResponse
    {
        $key = ltrim($objectKey, '/');

        $mime = $this->guessMimeType($key);

        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION) ?: '');
        if (in_array($ext, ['html', 'htm'], true)) {
            $html = $disk->get($key);
            abort_unless(is_string($html) && $html !== '', 404);

            $html = $this->injectScormApiShim($html);

            return response($html, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $stream = $disk->readStream($key);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            // Aggressivo: i pacchetti SCORM sono versionati per ID package.
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function injectScormApiShim(string $html): string
    {
        $shim = "<script>(function(){try{var p=window.parent; if(!p||p===window) return; window.API=window.API||p.API; window.API_1484_11=window.API_1484_11||p.API_1484_11;}catch(e){}})();</script>";

        if (stripos($html, '<head') !== false) {
            $out = preg_replace('#(<head\b[^>]*>)#i', '$1'.$shim, $html, 1);
            if (is_string($out) && $out !== '') {
                return $out;
            }
        }

        return $shim.$html;
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

