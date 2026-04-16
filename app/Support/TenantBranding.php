<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class TenantBranding
{
    public static function logoUrl(): ?string
    {
        $tenant = tenant();
        if (! $tenant) {
            return null;
        }

        $path = $tenant->brand_logo ?? null;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return tenant_asset($path);
    }
}
