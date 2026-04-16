<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;

/**
 * Stima uso storage media per il tenant corrente (prefisso S3 o disco tenant-isolato).
 *
 * @return array{0: int|null, 1: bool} bytes, known (false se troppi file o errore)
 */
final class MediaStorageUsageService
{
    public const MAX_FILES_TO_MEASURE = 25000;

    public function measureCurrentTenantMediaBytes(): array
    {
        $diskName = MediaStorage::disk();
        $driver = (string) config("filesystems.disks.{$diskName}.driver", 'local');

        try {
            $disk = Storage::disk($diskName);

            if ($driver === 's3') {
                $prefix = 'tenants/'.(string) tenant('id');
                $files = $disk->allFiles($prefix);
            } elseif ($driver === 'local') {
                $files = $disk->allFiles();
            } else {
                return [null, false];
            }

            if (count($files) > self::MAX_FILES_TO_MEASURE) {
                return [null, false];
            }

            $total = 0;
            foreach ($files as $path) {
                $total += $disk->size($path);
            }

            return [$total, true];
        } catch (\Throwable) {
            return [null, false];
        }
    }
}
