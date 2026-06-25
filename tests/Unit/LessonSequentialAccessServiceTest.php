<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\LessonType;
use App\Models\Tenant\Course;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Services\LessonSequentialAccessService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LessonSequentialAccessServiceTest extends TestCase
{
    private LessonSequentialAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LessonSequentialAccessService;
    }

    #[Test]
    public function first_lesson_is_accessible_without_prior_completion(): void
    {
        $course = $this->courseWithLessons(['Parte 1', 'Parte 2']);
        $lessons = $this->service->orderedLessons($course);

        $this->assertTrue(
            $this->service->isAccessible($course, $lessons[0], collect())
        );
    }

    #[Test]
    public function second_required_lesson_is_locked_until_first_is_completed(): void
    {
        $course = $this->courseWithLessons(['Parte 1', 'Parte 2']);
        $lessons = $this->service->orderedLessons($course);

        $this->assertFalse(
            $this->service->isAccessible($course, $lessons[1], collect())
        );

        $this->assertSame(
            $lessons[0]->id,
            $this->service->firstBlockingLesson($course, $lessons[1], collect())?->id
        );
    }

    #[Test]
    public function accessible_ids_stop_after_first_incomplete_required_lesson(): void
    {
        $course = $this->courseWithLessons(['Parte 1', 'Parte 2', 'Parte 3']);
        $lessons = $this->service->orderedLessons($course);

        $accessible = $this->service->accessibleLessonIds($course, collect());

        $this->assertSame(
            [$lessons[0]->id],
            $accessible->all()
        );
    }

    #[Test]
    public function optional_lessons_do_not_block_progression(): void
    {
        $course = $this->makeCourse();
        $module = $this->makeModule($course, 0);
        $first = $this->makeLesson($module, 'Parte 1', 0, true, LessonType::Video);
        $optional = $this->makeLesson($module, 'Extra', 1, false, LessonType::Video);
        $third = $this->makeLesson($module, 'Parte 2', 2, true, LessonType::Video);

        $course->setRelation('modules', collect([$module]));
        $module->setRelation('lessons', collect([$first, $optional, $third]));

        $completed = collect([$first->id]);

        $this->assertTrue($this->service->isAccessible($course, $third, $completed));
    }

    /**
     * @param  list<string>  $titles
     */
    private function courseWithLessons(array $titles): Course
    {
        $course = $this->makeCourse();
        $module = $this->makeModule($course, 0);
        $lessons = collect();

        foreach ($titles as $i => $title) {
            $lessons->push($this->makeLesson($module, $title, $i, true, LessonType::Video));
        }

        $module->setRelation('lessons', $lessons);
        $course->setRelation('modules', collect([$module]));

        return $course;
    }

    private function makeCourse(): Course
    {
        $course = new Course;
        $course->id = 'course-1';
        $course->exists = true;

        return $course;
    }

    private function makeModule(Course $course, int $position): Module
    {
        $module = new Module;
        $module->id = 'module-'.$position;
        $module->exists = true;
        $module->setRelation('pivot', (object) ['position' => $position, 'required' => true]);

        return $module;
    }

    private function makeLesson(
        Module $module,
        string $title,
        int $position,
        bool $required,
        LessonType $type,
    ): Lesson {
        $lesson = new Lesson;
        $lesson->id = 'lesson-'.$module->id.'-'.$position;
        $lesson->exists = true;
        $lesson->title = $title;
        $lesson->position = $position;
        $lesson->required = $required;
        $lesson->type = $type;
        $lesson->module_id = $module->id;

        return $lesson;
    }
}
