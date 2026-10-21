<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormTracking;
use App\Services\CourseScheduleService;
use App\Services\LessonSequentialAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    public function __construct(
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
        private readonly CourseScheduleService $courseSchedule,
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

        $lessonProgressPct = $this->lessonProgressPctMap($course, $enrollment, $completedLessonIds);

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

    /**
     * @param  \Illuminate\Support\Collection<int, string|int>  $completedLessonIds
     * @return array<string, int>
     */
    private function lessonProgressPctMap(Course $course, Enrollment $enrollment, $completedLessonIds): array
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

        return $map;
    }
}
