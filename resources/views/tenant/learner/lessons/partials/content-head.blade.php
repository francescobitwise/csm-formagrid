{{-- Intestazione dentro la card contenuto lezione --}}
@php
    $showLessonNav = $showLessonNav ?? true;
@endphp
<div class="learner-lesson-card-head">
    <div class="min-w-0 flex-1">
        <h1 class="learner-lesson-title">{{ $lesson->title }}</h1>
        @isset($hint)
            <p class="learner-lesson-subtitle">{{ $hint }}</p>
        @endisset
    </div>
    @if ($showLessonNav && (! empty($prevLessonId) || ! empty($nextLessonId)))
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if (! empty($prevLessonId))
                <x-ui.button href="{{ route('tenant.lessons.show', [$course, $prevLessonId]) }}" variant="secondary" size="sm" icon="ph-arrow-left">
                    Precedente
                </x-ui.button>
            @endif
            @if (! empty($nextLessonId))
                <x-ui.button href="{{ route('tenant.lessons.show', [$course, $nextLessonId]) }}" size="sm">
                    Successiva
                    <i class="ph ph-arrow-right"></i>
                </x-ui.button>
            @endif
        </div>
    @endif
</div>
