@props([
    'course',
    'lesson',
    'enrollment',
    'completedLessonIds',
    'accessibleLessonIds' => collect(),
    'completedCount' => 0,
    'totalCount' => 0,
    'lessonProgressPct' => [],
])

{{-- Blocco centrato a larghezza massima: player + elenco lezioni --}}
<div class="learner-lesson-shell">
    <div class="learner-lesson-workspace">
        <div class="learner-lesson-main min-w-0">
            {{ $slot }}
        </div>

        <aside class="learner-lesson-aside min-w-0">
            @include('tenant.learner.lessons.partials.sidebar', [
                'course' => $course,
                'currentLesson' => $lesson,
                'enrollment' => $enrollment,
                'completedLessonIds' => $completedLessonIds,
                'accessibleLessonIds' => $accessibleLessonIds,
                'completedCount' => $completedCount,
                'totalCount' => $totalCount,
                'lessonProgressPct' => $lessonProgressPct,
            ])
        </aside>
    </div>
</div>
