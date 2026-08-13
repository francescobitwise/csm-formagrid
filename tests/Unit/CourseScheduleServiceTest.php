<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Tenant\Course;
use App\Models\Tenant\User;
use App\Services\CourseScheduleService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourseScheduleServiceTest extends TestCase
{
    private CourseScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseScheduleService;
    }

    #[Test]
    public function schedule_disabled_is_always_open(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => false,
        ]);
        $learner = $this->makeUser(UserRole::Learner);

        $this->assertTrue($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 23:00:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function staff_bypasses_schedule(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [1, 2, 3, 4, 5],
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
        ]);
        $admin = $this->makeUser(UserRole::Admin);

        $this->assertTrue($this->service->isOpenFor(
            $course,
            $admin,
            Carbon::parse('2026-08-12 23:00:00', 'Europe/Rome') // mercoledì notte
        ));
    }

    #[Test]
    public function learner_is_open_during_day_window_on_selected_weekday(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [3], // mercoledì
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
        ]);
        $learner = $this->makeUser(UserRole::Learner);

        $this->assertTrue($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 10:30:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function learner_is_closed_outside_day_window(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [3],
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
        ]);
        $learner = $this->makeUser(UserRole::Learner);

        $this->assertFalse($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 20:00:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function learner_is_closed_on_non_selected_weekday(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [1, 2, 4, 5], // no mercoledì
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
        ]);
        $learner = $this->makeUser(UserRole::Learner);

        $this->assertFalse($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 10:00:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function night_override_opens_overnight_window(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [3],
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
            'night_schedule_enabled' => true,
            'night_opens_at' => '22:00',
            'night_closes_at' => '06:00',
        ]);
        $learner = $this->makeUser(UserRole::Learner, nightOverride: true);

        $this->assertTrue($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 23:30:00', 'Europe/Rome')
        ));

        $this->assertTrue($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-13 02:00:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function night_window_denied_without_override(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [3],
            'schedule_opens_at' => '09:00',
            'schedule_closes_at' => '18:00',
            'night_schedule_enabled' => true,
            'night_opens_at' => '22:00',
            'night_closes_at' => '06:00',
        ]);
        $learner = $this->makeUser(UserRole::Learner, nightOverride: false);

        $this->assertFalse($this->service->isOpenFor(
            $course,
            $learner,
            Carbon::parse('2026-08-12 23:30:00', 'Europe/Rome')
        ));
    }

    #[Test]
    public function schedule_summary_includes_day_and_night_labels(): void
    {
        $course = $this->makeCourse([
            'schedule_enabled' => true,
            'schedule_weekdays' => [1, 3, 5],
            'schedule_opens_at' => '09:00:00',
            'schedule_closes_at' => '18:00:00',
            'night_schedule_enabled' => true,
            'night_opens_at' => '22:00:00',
            'night_closes_at' => '06:00:00',
        ]);

        $summary = $this->service->scheduleSummaryFor($course);

        $this->assertTrue($summary['enabled']);
        $this->assertSame('Lun, Mer, Ven', $summary['weekdays_label']);
        $this->assertSame('09:00–18:00', $summary['day_range_label']);
        $this->assertSame('22:00–06:00', $summary['night_range_label']);
        $this->assertStringContainsString('Notturno (override)', $summary['summary_label']);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeCourse(array $attrs = []): Course
    {
        $course = new Course;
        $course->id = 'course-schedule-1';
        $course->exists = true;

        foreach ($attrs as $key => $value) {
            $course->setAttribute($key, $value);
        }

        return $course;
    }

    private function makeUser(UserRole $role, bool $nightOverride = false): User
    {
        $user = new User;
        $user->id = 'user-'.$role->value;
        $user->exists = true;
        $user->role = $role;
        $user->night_hours_override = $nightOverride;

        return $user;
    }
}
