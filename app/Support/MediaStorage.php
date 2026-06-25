<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
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
     * URL per una chiave oggetto sul disco MEDIA_DISK (es. tenants/.../cover.webp).
     * Su S3 privato usa URL firmati (presigned) così le copertine/poster sono visibili nel browser.
     */
    public static function url(string $objectKey, ?DateTimeInterface $expires = null): string
    {
        $disk = self::disk();
        $key = ltrim($objectKey, '/');

        if (self::shouldUseSignedObjectUrls($disk)) {
            $expires ??= now()->addMinutes((int) config('media.signed_object_ttl_minutes', 120));

            return Storage::disk($disk)->temporaryUrl($key, $expires);
        }

        return Storage::disk($disk)->url($key);
    }

    private static function shouldUseSignedObjectUrls(string $disk): bool
    {
        if ((string) config("filesystems.disks.{$disk}.driver") !== 's3') {
            return false;
        }

        $configured = config('media.signed_object_urls');
        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        if (self::uploadVisibility() === 'private') {
            return true;
        }

        // Bucket "owner enforced": senza ACL gli oggetti restano privati anche con visibility=public in config.
        return ! config('media.s3_put_acl', false);
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
