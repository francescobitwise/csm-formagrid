<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormTracking;
use App\Services\CourseScheduleService;
use App\Services\LearnerCourseProgress;
use App\Services\LessonSequentialAccessService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
        private readonly CourseScheduleService $courseSchedule,
        private readonly LearnerCourseProgress $learnerCourseProgress,
    ) {}

    public function show(Request $request, Course $course, Lesson $lesson)
    {
        $lesson->load(['module', 'videoLesson', 'scormPackage', 'documentLesson']);

        abort_unless(
            $course->modules()->where('modules.id', $lesson->module_id)->exists(),
            404
        );

        $user = $request->user();

        if (! $course->hasStarted()) {
            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', $course->notStartedMessage());
        }

        if (! $this->courseSchedule->isOpenFor($course, $user)) {
            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', $this->courseSchedule->closedMessage($course));
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->first();

        if ($enrollment === null) {
            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', 'Iscriviti al corso per accedere alle lezioni.');
        }

        $course->loadMissing([
            'modules.lessons' => fn ($q) => $q->orderBy('position'),
            'modules.lessons.videoLesson',
        ]);

        $completedLessonIds = $this->lessonSequentialAccess->completedLessonIdsForEnrollment($enrollment, (string) $user->id);

        if (! $this->lessonSequentialAccess->canAccessLesson($course, $lesson, $enrollment, $user)) {
            $blocking = $this->lessonSequentialAccess->firstBlockingLesson($course, $lesson, $completedLessonIds);

            if ($blocking !== null) {
                return redirect()
                    ->route('tenant.lessons.show', [$course, $blocking])
                    ->with('toast', 'Completa «'.$blocking->title.'» prima di accedere a questa lezione.');
            }

            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', 'Completa le lezioni precedenti prima di proseguire.');
        }

        $accessibleLessonIds = $this->lessonSequentialAccess->accessibleLessonIds($course, $completedLessonIds);
        $totalCount = (int) $course->modules->sum(fn ($m) => $m->lessons->count());
        $completedCount = (int) $completedLessonIds->count();

        $orderedIds = $this->lessonSequentialAccess->orderedLessons($course)->pluck('id')->values();
        $idx = $orderedIds->search($lesson->id);
        $prevLessonId = is_int($idx) && $idx > 0 ? $orderedIds[$idx - 1] : null;

        $nextLessonId = null;
        if (is_int($idx) && $idx < ($orderedIds->count() - 1)) {
            $candidateId = $orderedIds[$idx + 1];
            if ($completedLessonIds->contains($lesson->id) && $accessibleLessonIds->contains($candidateId)) {
                $nextLessonId = $candidateId;
            }
        }

        // Stato CMI salvato: permette al pacchetto SCORM di riprendere da dove
        // era rimasto invece di ripartire da zero a ogni apertura della lezione.
        $scormInitialCmi = null;
        if ((string) ($lesson->type?->value ?? $lesson->type) === 'scorm' && $lesson->scormPackage !== null) {
            $scormInitialCmi = ScormTracking::query()
                ->where('user_id', $user->id)
                ->where('scorm_package_id', $lesson->scormPackage->id)
                ->where('enrollment_id', $enrollment->id)
                ->value('data_model');

            if (is_string($scormInitialCmi)) {
                $scormInitialCmi = json_decode($scormInitialCmi, true);
            }
        }

        $lessonProgressPct = $this->learnerCourseProgress->lessonPercents($course, $enrollment, $completedLessonIds);

        $viewData = compact(
            'lesson',
            'course',
            'enrollment',
            'prevLessonId',
            'nextLessonId',
            'completedLessonIds',
            'accessibleLessonIds',
            'completedCount',
            'totalCount',
            'scormInitialCmi',
            'lessonProgressPct',
        );

        return match ((string) ($lesson->type?->value ?? $lesson->type)) {
            'video' => view('tenant.learner.lessons.video', $viewData),
            'scorm' => view('tenant.learner.lessons.scorm', $viewData),
            default => view('tenant.learner.lessons.document', $viewData),
        };
    }
}
