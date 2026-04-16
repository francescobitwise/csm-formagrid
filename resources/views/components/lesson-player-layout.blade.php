@props([
    'course',
    'lesson',
    'completedLessonIds',
    'completedCount' => 0,
    'totalCount' => 0,
])

<div class="mx-auto max-w-[1400px] px-6 py-10">
    <div class="grid gap-6 lg:grid-cols-[1fr_320px] lg:items-start">
        <div class="min-w-0">
            {{ $slot }}
        </div>

        <aside class="h-fit lg:sticky lg:top-24">
            @include('tenant.learner.lessons.partials.sidebar', [
                'course' => $course,
                'currentLesson' => $lesson,
                'completedLessonIds' => $completedLessonIds,
                'completedCount' => $completedCount,
                'totalCount' => $totalCount,
            ])
        </aside>
    </div>
</div>
