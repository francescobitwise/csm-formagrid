<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant\Course;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Services\LearnerCourseProgress;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LearnerCourseProgressTest extends TestCase
{
    #[Test]
    public function the_course_percent_is_the_average_of_required_lessons(): void
    {
        $course = $this->courseWithLessons([
            ['id' => 'a', 'required' => 1],
            ['id' => 'b', 'required' => 1],
        ]);

        $percent = LearnerCourseProgress::coursePercent($course, ['a' => 87, 'b' => 0]);

        $this->assertSame(43, $percent);
    }

    #[Test]
    public function optional_lessons_do_not_change_the_course_percent(): void
    {
        $course = $this->courseWithLessons([
            ['id' => 'a', 'required' => 1],
            ['id' => 'b', 'required' => 0],
        ]);

        $percent = LearnerCourseProgress::coursePercent($course, ['a' => 40, 'b' => 100]);

        $this->assertSame(40, $percent);
    }

    #[Test]
    public function a_completed_course_is_one_hundred_percent(): void
    {
        $course = $this->courseWithLessons([
            ['id' => 'a', 'required' => 1],
        ]);

        $percent = LearnerCourseProgress::coursePercent($course, ['a' => 20], true);

        $this->assertSame(100, $percent);
    }

    /**
     * @param  list<array{id: string, required: int}>  $lessons
     */
    private function courseWithLessons(array $lessons): Course
    {
        $models = collect($lessons)->map(function (array $lesson): Lesson {
            $model = new Lesson;
            $model->id = $lesson['id'];
            $model->required = $lesson['required'];

            return $model;
        });

        $module = new Module;
        $module->setRelation('lessons', new Collection($models));

        $course = new Course;
        $course->setRelation('modules', new Collection([$module]));

        return $course;
    }
}
