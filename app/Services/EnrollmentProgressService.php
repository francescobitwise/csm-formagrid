<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormTracking;
use App\Models\Tenant\VideoProgress;

class EnrollmentProgressService
{
    public function refresh(Enrollment $enrollment): void
    {
        $courseId = $enrollment->course_id;
        $userId = $enrollment->user_id;

        $requiredLessons = Lesson::query()
            ->whereHas('module', function ($q) use ($courseId) {
                $q->whereHas('courses', function ($cq) use ($courseId) {
                    $cq->where('courses.id', $courseId)
                        ->where('course_module.required', true);
                });
            })
            ->where('required', true)
            ->get(['id', 'type']);

        $totalRequired = $requiredLessons->count();

        if ($totalRequired === 0) {
            $enrollment->update([
                'progress_pct' => 100,
                'status' => EnrollmentStatus::Completed->value,
                'completed_at' => $enrollment->completed_at ?? now(),
            ]);
            $enrollment->refresh();
            app(CertificateIssuanceService::class)->ensureIssued($enrollment);

            return;
        }

        $requiredIds = $requiredLessons->pluck('id');

        $completedVideoLessonIds = VideoProgress::query()
            ->where('user_id', $userId)
            ->where('enrollment_id', $enrollment->id)
            ->where('completed', true)
            ->whereHas('videoLesson', fn ($q) => $q->whereIn('lesson_id', $requiredIds))
            ->with('videoLesson:id,lesson_id')
            ->get()
            ->pluck('videoLesson.lesson_id')
            ->filter()
            ->unique();

        $completedScormLessonIds = ScormTracking::query()
            ->where('user_id', $userId)
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('status', ['completed', 'passed'])
            ->whereHas('package', fn ($q) => $q->whereIn('lesson_id', $requiredIds))
            ->with('package:id,lesson_id')
            ->get()
            ->pluck('package.lesson_id')
            ->filter()
            ->unique();

        $completedRequired = $requiredLessons->filter(function ($lesson) use ($completedVideoLessonIds, $completedScormLessonIds) {
            $type = (string) ($lesson->type?->value ?? $lesson->type);

            return match ($type) {
                'video' => $completedVideoLessonIds->contains($lesson->id),
                'scorm' => $completedScormLessonIds->contains($lesson->id),
                default => false,
            };
        })->count();

        $pct = (int) floor(($completedRequired / $totalRequired) * 100);
        $isCompleted = $completedRequired >= $totalRequired;

        $enrollment->update([
            'progress_pct' => $pct,
            'status' => $isCompleted ? EnrollmentStatus::Completed->value : EnrollmentStatus::Active->value,
            'completed_at' => $isCompleted ? ($enrollment->completed_at ?? now()) : null,
        ]);

        if ($isCompleted) {
            $enrollment->refresh();
            app(CertificateIssuanceService::class)->ensureIssued($enrollment);
        }
    }
}
