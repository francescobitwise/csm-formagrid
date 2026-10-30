<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Percentuale mostrata all'allievo: la stessa dell'anello in lezione,
 * cioè la media del tempo già visto sulle lezioni obbligatorie.
 */
final class LearnerCourseProgress
{
    /**
     * @param  array<string, int>  $lessonPercents
     */
    public static function coursePercent(Course $course, array $lessonPercents, bool $completed = false): int
    {
        if ($completed) {
            return 100;
        }

        $values = [];
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if ((int) ($lesson->required ?? 1) !== 1) {
                    continue;
                }
                $values[] = (int) ($lessonPercents[(string) $lesson->id] ?? 0);
            }
        }

        if ($values === []) {
            return 0;
        }

        return (int) min(100, (int) floor(array_sum($values) / count($values)));
    }

    public function percentFor(Enrollment $enrollment, ?Course $course = null): int
    {
        if ($enrollment->status === EnrollmentStatus::Completed) {
            return 100;
        }

        $course ??= $enrollment->relationLoaded('course') ? $enrollment->course : null;
        if ($course === null) {
            $course = Course::query()->find($enrollment->course_id);
        }
        if ($course === null) {
            return 0;
        }

        $course->loadMissing([
            'modules.lessons' => fn ($q) => $q->orderBy('position'),
        ]);

        $completedIds = app(LessonSequentialAccessService::class)
            ->completedLessonIdsForEnrollment($enrollment, (string) $enrollment->user_id);

        return self::coursePercent(
            $course,
            $this->lessonPercents($course, $enrollment, $completedIds),
            false,
        );
    }

    /**
     * @param  Collection<int, string|int>  $completedLessonIds
     * @return array<string, int>
     */
    public function lessonPercents(Course $course, Enrollment $enrollment, Collection $completedLessonIds): array
    {
        $map = [];

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $courseLesson) {
                $map[(string) $courseLesson->id] = $completedLessonIds->contains($courseLesson->id) ? 100 : 0;
            }
        }

        $videoRows = DB::table('video_progress')
            ->join('video_lessons', 'video_lessons.id', '=', 'video_progress.video_lesson_id')
            ->where('video_progress.user_id', $enrollment->user_id)
            ->where('video_progress.enrollment_id', $enrollment->id)
            ->select([
                'video_lessons.lesson_id as lesson_id',
                'video_progress.watched_seconds as watched_seconds',
                'video_progress.completed as completed',
                'video_lessons.duration_seconds as duration_seconds',
            ])
            ->get();

        foreach ($videoRows as $row) {
            $lessonId = (string) ($row->lesson_id ?? '');
            if ($lessonId === '' || ! array_key_exists($lessonId, $map)) {
                continue;
            }

            if ((bool) ($row->completed ?? false) || $map[$lessonId] >= 100) {
                $map[$lessonId] = 100;

                continue;
            }

            $duration = (int) ($row->duration_seconds ?? 0);
            $watched = (int) ($row->watched_seconds ?? 0);
            if ($duration > 0 && $watched > 0) {
                $map[$lessonId] = (int) min(100, max(0, (int) floor(($watched / $duration) * 100)));
            }
        }

        $scormRows = DB::table('scorm_trackings')
            ->join('scorm_packages', 'scorm_packages.id', '=', 'scorm_trackings.scorm_package_id')
            ->join('lessons', 'lessons.id', '=', 'scorm_packages.lesson_id')
            ->where('scorm_trackings.user_id', $enrollment->user_id)
            ->where('scorm_trackings.enrollment_id', $enrollment->id)
            ->select([
                'scorm_packages.lesson_id as lesson_id',
                'scorm_trackings.watched_seconds as watched_seconds',
                'scorm_trackings.status as status',
                'lessons.duration_seconds as duration_seconds',
                'scorm_packages.manifest as manifest',
            ])
            ->get();

        foreach ($scormRows as $row) {
            $lessonId = (string) ($row->lesson_id ?? '');
            if ($lessonId === '' || ! array_key_exists($lessonId, $map)) {
                continue;
            }

            $status = strtolower((string) ($row->status ?? ''));
            if (in_array($status, ['completed', 'passed'], true) || $map[$lessonId] >= 100) {
                $map[$lessonId] = 100;

                continue;
            }

            $duration = (int) ($row->duration_seconds ?? 0);
            if ($duration <= 0) {
                $duration = $this->manifestDurationSeconds($row->manifest);
            }
            $watched = (int) ($row->watched_seconds ?? 0);
            if ($duration > 0 && $watched > 0) {
                $map[$lessonId] = (int) min(99, max(0, (int) floor(($watched / $duration) * 100)));
            }
        }

        return $map;
    }

    private function manifestDurationSeconds(mixed $manifest): int
    {
        if (is_string($manifest)) {
            $decoded = json_decode($manifest, true);
            $manifest = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($manifest)) {
            return 0;
        }

        return max(0, (int) ($manifest['duration_seconds'] ?? 0));
    }
}
