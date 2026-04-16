<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LessonType;
use App\Enums\ProcessingStatus;
use App\Jobs\ProcessVideoLessonUploadJob;
use App\Models\Tenant\Lesson;
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;

final class VideoLessonSourceService
{
    public function attachStoredSource(Lesson $lesson, string $storedPath, string $tenantId): void
    {
        if ($lesson->type !== LessonType::Video) {
            throw new \InvalidArgumentException('La lezione non è di tipo video.');
        }

        $disk = MediaStorage::disk();
        if (! Storage::disk($disk)->exists($storedPath)) {
            throw new \RuntimeException('File non trovato nello storage.');
        }

        $video = $lesson->videoLesson()->firstOrNew();
        if ($video->exists && is_string($video->poster_path) && $video->poster_path !== '') {
            Storage::disk($disk)->delete($video->poster_path);
        }

        $video->fill([
            'lesson_id' => $lesson->id,
            'original_s3' => $storedPath,
            'status' => ProcessingStatus::Processing->value,
            'poster_path' => null,
        ]);
        $video->save();

        ProcessVideoLessonUploadJob::dispatch($video->id, $tenantId);
    }
}
