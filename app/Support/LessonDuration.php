<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\LessonType;
use App\Models\Tenant\Lesson;

final class LessonDuration
{
    /**
     * @param  iterable<int, Lesson>  $lessons
     * @return array{total_seconds: int, lesson_count_with_duration: int}
     */
    public static function sumForLessons(iterable $lessons): array
    {
        $totalSeconds = 0;
        $lessonCountWithDuration = 0;

        foreach (collect($lessons) as $lesson) {
            $sec = $lesson->duration_seconds;
            if ($sec === null && ($lesson->type?->value ?? $lesson->type) === LessonType::Video->value) {
                $sec = $lesson->videoLesson?->duration_seconds;
            }
            if ($sec !== null) {
                $totalSeconds += (int) $sec;
                $lessonCountWithDuration++;
            }
        }

        return [
            'total_seconds' => $totalSeconds,
            'lesson_count_with_duration' => $lessonCountWithDuration,
        ];
    }
}
