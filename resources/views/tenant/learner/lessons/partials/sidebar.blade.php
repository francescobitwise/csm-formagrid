@php
    $formatLessonDuration = static function (?int $sec): ?string {
        $s = (int) ($sec ?? 0);
        if ($s <= 0) {
            return null;
        }
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $ss = $s % 60;
        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $ss);
        }

        return sprintf('%d:%02d', $m, $ss);
    };
@endphp

<div class="lesson-player-sidebar glass-card overflow-hidden rounded-2xl border border-white/5">
    <div class="border-b border-white/5 px-4 py-3">
        <a href="{{ route('tenant.courses.show', $course) }}"
           class="lesson-sidebar-back-link block truncate text-xs font-semibold">
            &larr; {{ $course->title }}
        </a>
        <div class="lesson-sidebar-progress mt-1 text-xs">
            {{ (int) $completedCount }} di {{ (int) $totalCount }} lezioni completate
        </div>
    </div>

    <div class="max-h-[calc(100vh-12rem)] divide-y divide-white/5 overflow-y-auto">
        @foreach ($course->modules as $module)
            @if ($course->modules->count() > 1)
                <div class="lesson-sidebar-module-heading bg-slate-100 px-4 py-2 text-[11px] font-semibold uppercase tracking-wider">
                    {{ $module->title }}
                </div>
            @endif

            @foreach ($module->lessons as $lessonItem)
                @php
                    $isCurrent = $lessonItem->id === $currentLesson->id;
                    $isCompleted = $completedLessonIds->contains($lessonItem->id);
                    $durSec = $lessonItem->duration_seconds ?? $lessonItem->videoLesson?->duration_seconds;
                    $durLabel = is_numeric($durSec) ? $formatLessonDuration((int) $durSec) : null;
                @endphp
                <a href="{{ route('tenant.lessons.show', [$course, $lessonItem]) }}"
                   @class([
                       'lesson-sidebar-lesson-row flex items-center gap-3 border-l-2 px-4 py-3 text-sm transition',
                       'lesson-sidebar-lesson-row--current border-brand-blue bg-brand-blue/10' => $isCurrent,
                       'border-transparent hover:bg-white/5' => ! $isCurrent,
                   ])>
                    <span class="shrink-0" aria-hidden="true">
                        @if ($isCompleted)
                            <i class="lesson-sidebar-icon-done ph ph-check-circle text-lg"></i>
                        @elseif ($isCurrent)
                            <i class="ph ph-play-circle text-lg text-brand-blue"></i>
                        @else
                            <i class="lesson-sidebar-icon-pending ph ph-circle text-lg"></i>
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span @class([
                            'lesson-sidebar-title block truncate font-medium',
                            'lesson-sidebar-title--current' => $isCurrent,
                            'lesson-sidebar-title--done' => ! $isCurrent && $isCompleted,
                            'lesson-sidebar-title--todo' => ! $isCurrent && ! $isCompleted,
                        ])>
                            {{ $lessonItem->title }}
                        </span>
                        @if ($durLabel)
                            <span class="lesson-sidebar-duration text-xs">{{ $durLabel }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        @endforeach
    </div>
</div>
