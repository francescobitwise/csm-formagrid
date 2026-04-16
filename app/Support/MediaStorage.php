<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class MediaStorage
{
    public static function disk(): string
    {
        return (string) config('media.disk', 'public');
    }

    public static function uploadVisibility(): string
    {
        return (string) config('media.upload_visibility', 'public');
    }

    /** @return array<string, mixed> */
    public static function putOptionsForDisk(string $disk, ?string $visibilityOverride = null): array
    {
        $driver = (string) config("filesystems.disks.{$disk}.driver");

        if ($driver === 's3' && ! config('media.s3_put_acl', false)) {
            return [];
        }

        return ['visibility' => $visibilityOverride ?? self::uploadVisibility()];
    }

    /**
     * URL pubblico per una chiave oggetto sul disco MEDIA_DISK (es. tenants/.../master.m3u8).
     * In DB conviene salvare sempre la chiave, non l’URL completo.
     */
    public static function url(string $objectKey): string
    {
        return Storage::disk(self::disk())->url(ltrim($objectKey, '/'));
    }

    /**
     * Da campo admin: accetta chiave o URL incollato da S3/R2 e restituisce la sola chiave da persistere.
     */
    public static function normalizeObjectKey(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return '';
        }

        if (preg_match('#^https?://[^/]+/(.+)$#', $input, $m)) {
            $path = explode('?', $m[1], 2)[0];

            return ltrim($path, '/');
        }

        return ltrim($input, '/');
    }
}
