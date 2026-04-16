<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ProcessingStatus;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VideoLesson extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'status' => ProcessingStatus::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (VideoLesson $video): void {
            if (is_string($video->poster_path) && $video->poster_path !== '') {
                Storage::disk(MediaStorage::disk())->delete($video->poster_path);
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function posterPublicUrl(): ?string
    {
        $path = $this->poster_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        return MediaStorage::url($path);
    }

    public function hlsManifestUrl(): ?string
    {
        if (! is_string($this->hls_manifest) || $this->hls_manifest === '') {
            return null;
        }

        return MediaStorage::url($this->hls_manifest);
    }
}
