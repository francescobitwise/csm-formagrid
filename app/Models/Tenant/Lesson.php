<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\LessonType;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'type' => LessonType::class,
        'duration_seconds' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function videoLesson(): HasOne
    {
        return $this->hasOne(VideoLesson::class);
    }

    public function scormPackage(): HasOne
    {
        return $this->hasOne(ScormPackage::class);
    }

    public function documentLesson(): HasOne
    {
        return $this->hasOne(DocumentLesson::class);
    }

    /**
     * URL del manifest HLS per il player learner: CDN diretta o rotta firmata (segmenti S3 presigned con TTL).
     */
    public function learnerHlsManifestUrl(Course $course): ?string
    {
        $video = $this->videoLesson;
        if ($video === null || ! is_string($video->hls_manifest) || $video->hls_manifest === '') {
            return null;
        }

        if (! config('media.signed_hls_manifest', false)) {
            return $video->hlsManifestUrl();
        }

        $disk = MediaStorage::disk();
        if (config("filesystems.disks.{$disk}.driver") !== 's3') {
            return $video->hlsManifestUrl();
        }

        return route('tenant.learner.hls.manifest', ['course' => $course, 'lesson' => $this], absolute: true);
    }
}
