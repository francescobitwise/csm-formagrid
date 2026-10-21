<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logo organizzazione per PDF (data URI) — stesso disco `public` del branding tenant.
 */
final class TenantPdfLogo
{
    public static function dataUri(): ?string
    {
        $tenant = tenant();
        if (! $tenant) {
            return null;
        }

        $path = $tenant->brand_logo ?? null;
        $fullPath = null;
        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            $fullPath = Storage::disk('public')->path($path);
        } else {
            $fallback = public_path('brand/logo-confederazione-servizi.png');
            $fullPath = is_file($fallback) ? $fallback : null;
        }

        if ($fullPath === null) {
            return null;
        }
        $bin = @file_get_contents($fullPath);
        if (! is_string($bin) || $bin === '') {
            return null;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: '');
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($bin);
    }
}
