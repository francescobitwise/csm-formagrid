<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\EnrollmentProgressService;
use App\Services\SessionLessonProgressService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionLessonProgressServiceTest extends TestCase
{
    private SessionLessonProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SessionLessonProgressService(new EnrollmentProgressService);
    }

    #[Test]
    public function time_without_a_declared_duration_completes_the_lesson(): void
    {
        $this->assertTrue($this->service->creditsCoverLesson(60, null));
        $this->assertFalse($this->service->creditsCoverLesson(0, null));
    }

    #[Test]
    public function time_must_cover_almost_the_whole_lesson_duration(): void
    {
        $duration = 60 * 60;

        $this->assertFalse($this->service->creditsCoverLesson(50 * 60, $duration));
        $this->assertTrue($this->service->creditsCoverLesson(58 * 60, $duration));
        $this->assertTrue($this->service->creditsCoverLesson($duration, $duration));
    }
}
