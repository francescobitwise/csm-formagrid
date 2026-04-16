<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class UploadedFileStorage
{
    public static function put(
        UploadedFile $file,
        string $disk,
        string $relativePath,
        ?string $visibility = null,
    ): string|false {
        $stream = self::openUploadReadStream($file);
        if ($stream === false) {
            Log::warning('UploadedFileStorage: impossibile aprire lo stream dell\'upload.', [
                'path' => $relativePath,
            ]);

            return false;
        }

        $options = MediaStorage::putOptionsForDisk($disk, $visibility);

        try {
            $ok = Storage::disk($disk)->put($relativePath, $stream, $options);

            if ($ok !== true) {
                Log::warning('UploadedFileStorage: Storage::put fallito.', [
                    'disk' => $disk,
                    'path' => $relativePath,
                    'driver' => config("filesystems.disks.{$disk}.driver"),
                ]);
            }

            return $ok === true ? $relativePath : false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @return resource|false
     */
    private static function openUploadReadStream(UploadedFile $file)
    {
        $path = $file->getPathname();
        $stream = @fopen($path, 'rb');
        if ($stream !== false) {
            return $stream;
        }

        $real = $file->getRealPath();
        if ($real !== false && $real !== $path) {
            return @fopen($real, 'rb');
        }

        return false;
    }
}
