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
    $accessibleLessonIds = $accessibleLessonIds ?? collect();
@endphp

<div class="card bordered bg-base-100 overflow-hidden shadow-xl">
    <div class="border-b border-base-300 px-4 py-3">
        <a href="{{ route('tenant.courses.show', $course) }}"
           class="link link-hover block truncate text-xs font-semibold">
            &larr; {{ $course->title }}
        </a>
        <div class="mt-1 text-xs text-base-content/60">
            {{ (int) $completedCount }} di {{ (int) $totalCount }} lezioni completate
        </div>
    </div>

    <div class="max-h-[calc(100vh-12rem)] divide-y divide-base-300 overflow-y-auto">
        @foreach ($course->modules as $module)
            @if ($course->modules->count() > 1)
                <div class="bg-base-200 px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-base-content/60">
                    {{ $module->title }}
                </div>
            @endif

            @foreach ($module->lessons as $lessonItem)
                @php
                    $isCurrent = $lessonItem->id === $currentLesson->id;
                    $isCompleted = $completedLessonIds->contains($lessonItem->id);
                    $isAccessible = $accessibleLessonIds->contains($lessonItem->id);
                    $durSec = $lessonItem->duration_seconds ?? $lessonItem->videoLesson?->duration_seconds;
                    $durLabel = is_numeric($durSec) ? $formatLessonDuration((int) $durSec) : null;
                @endphp

                @if ($isAccessible)
                    <a href="{{ route('tenant.lessons.show', [$course, $lessonItem]) }}"
                       @class([
                           'flex items-center gap-3 border-l-2 px-4 py-3 text-sm transition',
                           'border-primary bg-primary/10' => $isCurrent,
                           'border-transparent hover:bg-base-200' => ! $isCurrent,
                       ])>
                        <span class="shrink-0" aria-hidden="true">
                            @if ($isCompleted)
                                <i class="ph ph-check-circle text-lg text-success"></i>
                            @elseif ($isCurrent)
                                <i class="ph ph-play-circle text-lg text-primary"></i>
                            @else
                                <i class="ph ph-circle text-lg text-base-content/40"></i>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span @class([
                                'block truncate font-medium',
                                'text-primary' => $isCurrent,
                                'text-base-content/60' => ! $isCurrent && $isCompleted,
                            ])>
                                {{ $lessonItem->title }}
                            </span>
                            @if ($durLabel)
                                <span class="text-xs text-base-content/50">{{ $durLabel }}</span>
                            @endif
                        </span>
                    </a>
                @else
                    <div class="flex items-center gap-3 border-l-2 border-transparent px-4 py-3 text-sm opacity-60"
                         title="Completa le lezioni precedenti per sbloccare">
                        <span class="shrink-0" aria-hidden="true">
                            <i class="ph ph-lock-key text-lg text-base-content/40"></i>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-base-content/50">
                                {{ $lessonItem->title }}
                            </span>
                            @if ($durLabel)
                                <span class="text-xs text-base-content/40">{{ $durLabel }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>
