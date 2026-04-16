<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Support\LessonDuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = $user->id;
        $companyId = $user->company_id;
        $q = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->where('status', CourseStatus::Published)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->when(! $user->isStaffMember(), function ($query) use ($userId, $companyId): void {
                $query->where(function ($inner) use ($userId, $companyId): void {
                    $inner->whereExists(function ($sub) use ($userId) {
                        $sub->selectRaw('1')
                            ->from('course_user_assignments')
                            ->whereColumn('course_user_assignments.course_id', 'courses.id')
                            ->where('course_user_assignments.user_id', $userId);
                    });

                    if ($companyId !== null) {
                        $inner->orWhereExists(function ($sub) use ($companyId) {
                            $sub->selectRaw('1')
                                ->from('course_company_assignments')
                                ->whereColumn('course_company_assignments.course_id', 'courses.id')
                                ->where('course_company_assignments.company_id', $companyId);
                        });
                    }
                });
            })
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->withCount(['modules', 'lessons'])
            ->withExists([
                'enrollments as user_enrolled' => fn ($sub) => $sub->where('user_id', $userId)
                    ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed]),
            ])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('tenant.learner.courses.index', [
            'courses' => $courses,
            'q' => $q,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->status === CourseStatus::Published, 404);
        abort_if($course->starts_at && $course->starts_at->isFuture(), 404);
        abort_unless($course->isVisibleToUser($request->user()), 404);

        $course->load([
            'modules.lessons' => fn ($q) => $q->orderBy('position'),
            'modules.lessons.videoLesson',
        ]);

        $enrollment = Enrollment::query()
            ->where('user_id', $request->user()->id)
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
        $nextLessonId = null;
        $requiredLessonIds = collect();

        if ($enrollment) {
            $userId = $request->user()->id;

            $requiredLessonIds = $course->modules
                ->filter(fn ($m) => (bool) ($m->pivot?->required ?? true))
                ->flatMap(fn ($m) => $m->lessons->where('required', true)->pluck('id'))
                ->values();

            $videoRows = DB::table('video_progress')
                ->join('video_lessons', 'video_lessons.id', '=', 'video_progress.video_lesson_id')
                ->where('video_progress.user_id', $userId)
                ->where('video_progress.enrollment_id', $enrollment->id)
                ->select([
                    'video_lessons.lesson_id as lesson_id',
                    'video_progress.completed as completed',
                    'video_progress.watched_seconds as watched_seconds',
                ])->get();

            $completedVideoLessonIds = collect($videoRows)
                ->filter(fn ($r) => (bool) $r->completed)
                ->pluck('lesson_id')
                ->filter();

            $startedVideoLessonIds = collect($videoRows)
                ->filter(fn ($r) => (int) ($r->watched_seconds ?? 0) > 0)
                ->pluck('lesson_id')
                ->filter();

            $completedScormLessonIds = DB::table('scorm_trackings')
                ->join('scorm_packages', 'scorm_packages.id', '=', 'scorm_trackings.scorm_package_id')
                ->where('scorm_trackings.user_id', $userId)
                ->where('scorm_trackings.enrollment_id', $enrollment->id)
                ->whereIn('scorm_trackings.status', ['completed', 'passed'])
                ->pluck('scorm_packages.lesson_id')
                ->filter();

            $completedLessonIds = $completedVideoLessonIds->merge($completedScormLessonIds)->unique()->values();
            $startedLessonIds = $startedVideoLessonIds->unique()->values();

            $orderedLessons = $course->modules
                ->sortBy(fn ($m) => (int) ($m->pivot?->position ?? 0))
                ->flatMap(fn ($m) => $m->lessons);

            $nextRequired = $orderedLessons->first(function ($lesson) use ($completedLessonIds, $requiredLessonIds) {
                if (! $requiredLessonIds->contains($lesson->id)) {
                    return false;
                }

                return ! $completedLessonIds->contains($lesson->id);
            });

            $nextAny = $orderedLessons->first(fn ($lesson) => ! $completedLessonIds->contains($lesson->id));

            $nextLessonId = $nextRequired?->id ?? $nextAny?->id ?? $orderedLessons->first()?->id;
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
            'requiredLessonIds' => $requiredLessonIds,
            'requiredCompletedCount' => $requiredCompletedCount,
            'nextLessonId' => $nextLessonId,
        ]);
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);
        abort_if($course->starts_at && $course->starts_at->isFuture(), 404);
        abort_unless($course->isVisibleToUser($request->user()), 404);

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
