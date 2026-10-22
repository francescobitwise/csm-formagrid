<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manifest HLS riscritto e segmenti letti dal disco media, serviti dal sito.
 */
final class HlsPlaylist
{
    /**
     * @param  Closure(string, string): (?string)  $absoluteUrl
     */
    public function manifestResponse(Filesystem $disk, string $manifestKey, Closure $absoluteUrl): SymfonyResponse
    {
        $body = (string) $disk->get($manifestKey);

        return response($this->rewrite($body, $manifestKey, $absoluteUrl), 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param  Closure(string, string): (?string)  $absoluteUrl
     */
    public function fileResponse(Filesystem $disk, string $manifestKey, string $file, Closure $absoluteUrl): StreamedResponse|SymfonyResponse
    {
        $dir = $this->hlsDirectory($manifestKey);
        $key = $this->resolveRelativeObjectKey($dir, $file);
        abort_unless($key !== null && $this->isRewritablePlaylistRef($file), 404);
        abort_unless($disk->exists($key), 404);

        if (str_ends_with(strtolower($file), '.m3u8')) {
            $body = (string) $disk->get($key);

            return response($this->rewrite($body, $key, $absoluteUrl), 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $stream = $disk->readStream($key);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->mimeForHlsFile($key),
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * @param  Closure(string, string): (?string)  $absoluteUrl
     */
    public function rewrite(string $body, string $playlistKey, Closure $absoluteUrl): string
    {
        $dir = $this->hlsDirectory($playlistKey);
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $trim = rtrim($line, "\r\n");
            $t = trim($trim);

            if ($t === '' || str_starts_with($t, '#')) {
                if (str_contains($t, 'URI="') && preg_match('/URI="([^"]+)"/', $t, $m)) {
                    $uri = $m[1];
                    $absolute = $absoluteUrl($dir, $uri);
                    if (is_string($absolute) && $absolute !== '') {
                        $escaped = str_replace('"', '%22', $absolute);
                        $out[] = (string) preg_replace('/URI="[^"]+"/', 'URI="'.$escaped.'"', $trim, 1);

                        continue;
                    }
                }
                $out[] = $trim;

                continue;
            }

            if (str_contains($t, '://')) {
                $out[] = $trim;

                continue;
            }

            $absolute = $absoluteUrl($dir, $t);
            $out[] = (is_string($absolute) && $absolute !== '') ? $absolute : $trim;
        }

        return implode("\n", $out);
    }

    public function hlsDirectory(string $manifestKey): string
    {
        return trim(dirname($manifestKey), '/.');
    }

    public function resolveRelativeObjectKey(string $dir, string $ref): ?string
    {
        $ref = trim(str_replace('\\', '/', $ref));
        if ($ref === '' || str_contains($ref, '://') || str_contains($ref, '..')) {
            return null;
        }

        $ref = ltrim($ref, '/');
        if ($ref === '' || strlen($ref) > 512) {
            return null;
        }

        if (! preg_match('/^[a-zA-Z0-9._\-\/]+$/', $ref)) {
            return null;
        }

        if ($dir === '' || $dir === '.') {
            return $ref;
        }

        return $dir.'/'.$ref;
    }

    public function isRewritablePlaylistRef(string $ref): bool
    {
        $base = basename(str_replace('\\', '/', $ref));

        return (bool) preg_match('/\.(m3u8|ts|m4s|aac|mp4|vtt)$/i', $base);
    }

    public function mimeForHlsFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'm4s' => 'video/iso.segment',
            'mp4' => 'video/mp4',
            'aac' => 'audio/aac',
            'vtt' => 'text/vtt',
            default => 'application/octet-stream',
        };
    }
}
