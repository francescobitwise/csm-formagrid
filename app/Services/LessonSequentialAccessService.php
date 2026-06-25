<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Progressione sequenziale tra lezioni (capitoli) del corso: una lezione obbligatoria
 * video/SCORM deve essere completata prima di aprire le successive.
 */
final class LessonSequentialAccessService
{
    /**
     * @return Collection<int, string>
     */
    public function completedLessonIdsForEnrollment(Enrollment $enrollment, string $userId): Collection
    {
        $videoRows = DB::table('video_progress')
            ->join('video_lessons', 'video_lessons.id', '=', 'video_progress.video_lesson_id')
            ->where('video_progress.user_id', $userId)
            ->where('video_progress.enrollment_id', $enrollment->id)
            ->select([
                'video_lessons.lesson_id as lesson_id',
                'video_progress.completed as completed',
            ])->get();

        $completedVideoLessonIds = collect($videoRows)
            ->filter(fn ($r) => (bool) $r->completed)
            ->pluck('lesson_id')
            ->filter();

        $completedScormLessonIds = DB::table('scorm_trackings')
            ->join('scorm_packages', 'scorm_packages.id', '=', 'scorm_trackings.scorm_package_id')
            ->where('scorm_trackings.user_id', $userId)
            ->where('scorm_trackings.enrollment_id', $enrollment->id)
            ->whereIn('scorm_trackings.status', ['completed', 'passed'])
            ->pluck('scorm_packages.lesson_id')
            ->filter();

        return $completedVideoLessonIds->merge($completedScormLessonIds)->unique()->values();
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function orderedLessons(Course $course): Collection
    {
        $course->loadMissing([
            'modules.lessons' => fn ($q) => $q->orderBy('position'),
        ]);

        return $course->modules
            ->sortBy(fn ($m) => (int) ($m->pivot?->position ?? 0))
            ->values()
            ->flatMap(fn ($m) => $m->lessons)
            ->values();
    }

    public function isAccessible(Course $course, Lesson $lesson, Collection $completedLessonIds): bool
    {
        return $this->firstBlockingLesson($course, $lesson, $completedLessonIds) === null;
    }

    public function canAccessLesson(
        Course $course,
        Lesson $lesson,
        Enrollment $enrollment,
        User $user,
    ): bool {
        if ($user->isStaffMember()) {
            return true;
        }

        return $this->isAccessible(
            $course,
            $lesson,
            $this->completedLessonIdsForEnrollment($enrollment, (string) $user->id),
        );
    }

    public function firstBlockingLesson(Course $course, Lesson $lesson, Collection $completedLessonIds): ?Lesson
    {
        foreach ($this->orderedLessons($course) as $orderedLesson) {
            if ($orderedLesson->id === $lesson->id) {
                return null;
            }

            if ($this->lessonBlocksProgression($orderedLesson) && ! $completedLessonIds->contains($orderedLesson->id)) {
                return $orderedLesson;
            }
        }

        return null;
    }

    /**
     * Lezioni apribili: completate + la prima incompleta obbligatoria in coda.
     *
     * @return Collection<int, string>
     */
    public function accessibleLessonIds(Course $course, Collection $completedLessonIds): Collection
    {
        $accessible = collect();

        foreach ($this->orderedLessons($course) as $orderedLesson) {
            $accessible->push($orderedLesson->id);

            if ($this->lessonBlocksProgression($orderedLesson) && ! $completedLessonIds->contains($orderedLesson->id)) {
                break;
            }
        }

        return $accessible->values();
    }

    private function lessonBlocksProgression(Lesson $lesson): bool
    {
        if (! (bool) ($lesson->required ?? false)) {
            return false;
        }

        $type = (string) ($lesson->type?->value ?? $lesson->type);

        return in_array($type, ['video', 'scorm'], true);
    }
}
