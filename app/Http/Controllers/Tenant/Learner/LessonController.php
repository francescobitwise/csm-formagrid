<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Services\LessonSequentialAccessService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
    ) {}

    public function show(Request $request, Course $course, Lesson $lesson)
    {
        $lesson->load(['module', 'videoLesson', 'scormPackage', 'documentLesson']);

        abort_unless(
            $course->modules()->where('modules.id', $lesson->module_id)->exists(),
            404
        );

        $enrollment = Enrollment::query()
            ->where('user_id', $request->user()->id)
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
        ]);

        $user = $request->user();
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
        );

        return match ((string) ($lesson->type?->value ?? $lesson->type)) {
            'video' => view('tenant.learner.lessons.video', $viewData),
            'scorm' => view('tenant.learner.lessons.scorm', $viewData),
            default => view('tenant.learner.lessons.document', $viewData),
        };
    }
}
