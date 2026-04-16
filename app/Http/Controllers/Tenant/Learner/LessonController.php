<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
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

        $userId = (string) $request->user()->id;
        $completedLessonIds = $this->completedLessonIdsForEnrollment($enrollment, $userId);
        $totalCount = (int) $course->modules->sum(fn ($m) => $m->lessons->count());
        $completedCount = (int) $completedLessonIds->count();

        $sidebar = [
            'completedLessonIds' => $completedLessonIds,
            'completedCount' => $completedCount,
            'totalCount' => $totalCount,
        ];

        $ordered = Lesson::query()
            ->join('course_module', 'course_module.module_id', '=', 'lessons.module_id')
            ->where('course_module.course_id', $course->id)
            ->orderBy('course_module.position')
            ->orderBy('lessons.position')
            ->get(['lessons.id']);

        $ids = $ordered->pluck('id')->values();
        $idx = $ids->search($lesson->id);
        $prevLessonId = is_int($idx) && $idx > 0 ? $ids[$idx - 1] : null;
        $nextLessonId = is_int($idx) && $idx < ($ids->count() - 1) ? $ids[$idx + 1] : null;

        $viewData = array_merge(
            compact('lesson', 'course', 'enrollment', 'prevLessonId', 'nextLessonId'),
            $sidebar,
        );

        return match ((string) ($lesson->type?->value ?? $lesson->type)) {
            'video' => view('tenant.learner.lessons.video', $viewData),
            'scorm' => view('tenant.learner.lessons.scorm', $viewData),
            default => view('tenant.learner.lessons.document', $viewData),
        };
    }

    /**
     * Lezioni considerate “completate” (video con flag completed; SCORM completed/passed).
     *
     * @return Collection<int, string>
     */
    private function completedLessonIdsForEnrollment(Enrollment $enrollment, string $userId): Collection
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
}
