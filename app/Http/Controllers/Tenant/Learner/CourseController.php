<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Services\CourseScheduleService;
use App\Services\LessonSequentialAccessService;
use App\Support\LessonDuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
        private readonly CourseScheduleService $courseSchedule,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = $user->id;
        $q = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->publishedForLearner($user)
            ->searchLearnerCatalog($q)
            ->withCount(['modules', 'lessons'])
            ->withExists([
                'enrollments as user_enrolled' => fn ($sub) => $sub->where('user_id', $userId)
                    ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed]),
            ])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $courses->getCollection()->transform(function (Course $course) use ($user) {
            $course->setAttribute('has_started', $course->hasStarted());
            $course->setAttribute('is_schedule_open', $this->courseSchedule->isOpenFor($course, $user));
            $course->setAttribute('schedule_summary', $this->courseSchedule->scheduleSummaryFor($course));

            return $course;
        });

        return view('tenant.learner.courses.index', [
            'courses' => $courses,
            'q' => $q,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->status === CourseStatus::Published, 404);
        abort_unless($course->isVisibleToUser($request->user()), 404);

        $user = $request->user();
        $hasStarted = $course->hasStarted();
        $notStartedMessage = $hasStarted ? null : $course->notStartedMessage();
        $isScheduleOpen = $hasStarted && $this->courseSchedule->isOpenFor($course, $user);
        $scheduleSummary = $this->courseSchedule->scheduleSummaryFor($course);
        $closedMessage = ($hasStarted && ! $isScheduleOpen)
            ? $this->courseSchedule->closedMessage($course)
            : null;

        $course->load([
            'modules.lessons' => fn ($q) => $q->orderBy('position'),
            'modules.lessons.videoLesson',
        ]);

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->first();

        $orgName = (string) (tenant('organization_name') ?? tenant('id') ?? '');
        $pageTitle = trim($orgName) !== '' ? ($course->title.' — '.$orgName) : $course->title;

        $moduleMeta = [];
        foreach ($course->modules as $module) {
            $sum = LessonDuration::sumForLessons($module->lessons);
            $moduleMeta[$module->id] = [
                'lesson_count' => $module->lessons->count(),
                'total_seconds' => (int) ($sum['total_seconds'] ?? 0),
            ];
        }

        $completedLessonIds = collect();
        $startedLessonIds = collect();
        $accessibleLessonIds = collect();
        $nextLessonId = null;
        $requiredLessonIds = collect();

        if ($enrollment) {
            $userId = $user->id;

            $requiredLessonIds = $course->modules
                ->filter(fn ($m) => (bool) ($m->pivot?->required ?? true))
                ->flatMap(fn ($m) => $m->lessons->where('required', true)->pluck('id'))
                ->values();

            $completedLessonIds = $this->lessonSequentialAccess->completedLessonIdsForEnrollment($enrollment, (string) $userId);
            $accessibleLessonIds = $this->lessonSequentialAccess->accessibleLessonIds($course, $completedLessonIds);

            $videoRows = DB::table('video_progress')
                ->join('video_lessons', 'video_lessons.id', '=', 'video_progress.video_lesson_id')
                ->where('video_progress.user_id', $userId)
                ->where('video_progress.enrollment_id', $enrollment->id)
                ->select([
                    'video_lessons.lesson_id as lesson_id',
                    'video_progress.completed as completed',
                    'video_progress.watched_seconds as watched_seconds',
                ])->get();

            $startedVideoLessonIds = collect($videoRows)
                ->filter(fn ($r) => (int) ($r->watched_seconds ?? 0) > 0)
                ->pluck('lesson_id')
                ->filter();

            $startedLessonIds = $startedVideoLessonIds->unique()->values();

            $orderedLessons = $this->lessonSequentialAccess->orderedLessons($course);

            $nextLesson = $orderedLessons->first(function ($lesson) use ($completedLessonIds, $accessibleLessonIds) {
                return $accessibleLessonIds->contains($lesson->id)
                    && ! $completedLessonIds->contains($lesson->id);
            });

            $nextLessonId = $nextLesson?->id ?? $orderedLessons->first()?->id;
        }

        $requiredCompletedCount = $requiredLessonIds->isEmpty()
            ? 0
            : $completedLessonIds->intersect($requiredLessonIds)->count();

        return view('tenant.learner.courses.show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'pageTitle' => $pageTitle,
            'moduleMeta' => $moduleMeta,
            'completedLessonIds' => $completedLessonIds,
            'startedLessonIds' => $startedLessonIds,
            'accessibleLessonIds' => $accessibleLessonIds,
            'requiredLessonIds' => $requiredLessonIds,
            'requiredCompletedCount' => $requiredCompletedCount,
            'nextLessonId' => $nextLessonId,
            'hasStarted' => $hasStarted,
            'notStartedMessage' => $notStartedMessage,
            'isScheduleOpen' => $isScheduleOpen,
            'scheduleSummary' => $scheduleSummary,
            'closedMessage' => $closedMessage,
        ]);
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);
        abort_unless($course->isVisibleToUser($request->user()), 404);

        if (! $course->hasStarted()) {
            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', $course->notStartedMessage());
        }

        if (! $this->courseSchedule->isOpenFor($course, $request->user())) {
            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', $this->courseSchedule->closedMessage($course));
        }

        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
            ],
            [
                'status' => EnrollmentStatus::Active->value,
                'progress_pct' => 0,
                'enrolled_at' => now(),
            ],
        );

        if (! $enrollment->wasRecentlyCreated) {
            if ($enrollment->status === EnrollmentStatus::Expired) {
                $enrollment->update([
                    'status' => EnrollmentStatus::Active->value,
                    'enrolled_at' => now(),
                ]);

                return redirect()
                    ->route('tenant.courses.show', $course)
                    ->with('toast', 'Iscrizione riattivata.');
            }

            return redirect()
                ->route('tenant.courses.show', $course)
                ->with('toast', 'Sei già iscritto a questo corso.');
        }

        return redirect()
            ->route('tenant.courses.show', $course)
            ->with('toast', 'Iscrizione al corso completata. Buono studio!');
    }
}
