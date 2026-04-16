<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

/**
 * Single-client compatibility helpers.
 *
 * The original codebase was multi-tenant and relied on Stancl's `tenant()` and `tenant_asset()`.
 * In the single-client fork we keep the same API surface backed by global config/env values.
 */
if (! function_exists('tenant')) {
    /**
     * @return object|string|array<int, mixed>|null
     */
    function tenant(?string $key = null): mixed
    {
        $idFromAppName = (string) Str::of((string) config('app.name', ''))->slug('_');
        $idFromAppName = $idFromAppName !== '' ? $idFromAppName : 'single';

        $defaults = (object) [
            // Single-client: derive a stable identifier from APP_NAME (organization name).
            'id' => $idFromAppName,
            // Organization display name.
            'organization_name' => (string) config('app.name', ''),
            'contact_email' => null,
            'brand_logo' => (string) config('branding.logo_path', 'brand/logo.png'),
            'pdf_course_report' => [
                'header' => '',
                'footer' => '',
                'accent' => (string) config('branding.accent', '#1a6dbf'),
            ],
        ];

        // Best-effort DB-backed settings. Avoid breaking during early bootstrap/migrations.
        try {
            if (Schema::hasTable('settings')) {
                $defaults->contact_email = Setting::get('contact_email', $defaults->contact_email);
                $defaults->brand_logo = (string) (Setting::get('brand_logo', $defaults->brand_logo) ?? $defaults->brand_logo);
                $storedPdf = (array) (Setting::get('pdf_course_report', []) ?? []);
                $mergedPdf = array_merge((array) $defaults->pdf_course_report, $storedPdf);
                $accent = trim((string) ($mergedPdf['accent'] ?? ''));
                $mergedPdf['accent'] = $accent !== '' ? $accent : (string) ($defaults->pdf_course_report['accent'] ?? '#1a6dbf');
                $defaults->pdf_course_report = $mergedPdf;
            }
        } catch (Throwable) {
            // ignore
        }

        if ($key === null) {
            return $defaults;
        }

        return $defaults->{$key} ?? null;
    }
}

if (! function_exists('tenant_asset')) {
    function tenant_asset(string $path): string
    {
        // In single-client mode branding assets live on the public disk.
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url(ltrim($path, '/'));
    }
}

