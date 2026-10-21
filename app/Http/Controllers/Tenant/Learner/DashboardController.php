<?php

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Certificate;
use App\Models\Tenant\Course;
use App\Services\CourseScheduleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CourseScheduleService $courseSchedule,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $enrollments = $user
            ->enrollments()
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->with(['course'])
            ->orderByDesc('enrolled_at')
            ->get();

        $enrolledCourseIds = $enrollments->pluck('course_id')->filter()->values();

        $availableCourses = Course::query()
            ->publishedForLearner($user)
            ->when($enrolledCourseIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $enrolledCourseIds))
            ->withCount(['modules', 'lessons'])
            ->orderBy('title')
            ->limit(12)
            ->get();

        $annotate = function (Course $course) use ($user): Course {
            $course->setAttribute('has_started', $course->hasStarted());
            $course->setAttribute('is_schedule_open', $this->courseSchedule->isOpenFor($course, $user));
            $course->setAttribute('schedule_summary', $this->courseSchedule->scheduleSummaryFor($course));

            return $course;
        };

        $availableCourses->transform($annotate);
        foreach ($enrollments as $enrollment) {
            if ($enrollment->course instanceof Course) {
                $annotate($enrollment->course);
            }
        }

        $count = $enrollments->count();
        $avgProgress = $count > 0
            ? (int) floor($enrollments->avg(fn ($e) => (float) $e->progress_pct))
            : 0;

        $certificateCount = Certificate::query()
            ->where('user_id', $user->id)
            ->count();

        return view('tenant.dashboard', compact(
            'enrollments',
            'availableCourses',
            'count',
            'avgProgress',
            'certificateCount',
        ));
    }
}
