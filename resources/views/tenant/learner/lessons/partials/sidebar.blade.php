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
    $lessonProgressPct = $lessonProgressPct ?? [];
    $total = max(1, (int) $totalCount);
    $completed = (int) $completedCount;
    $coursePct = isset($enrollment)
        ? (int) min(100, max(0, (float) ($enrollment->progress_pct ?? 0)))
        : (int) min(100, max(0, (int) floor(($completed / $total) * 100)));
    $currentModuleId = $currentLesson->module_id ?? null;
    $r = 18;
    $c = 2 * M_PI * $r;
    $offset = $c * (1 - ($coursePct / 100));
@endphp

{{-- Una sola card: progresso + contenuti --}}
<div class="flex max-h-[min(36rem,calc(100dvh-5.5rem))] min-h-0 flex-col overflow-hidden rounded-xl border border-base-300 bg-base-100 shadow-sm lg:max-h-[calc(100dvh-5.5rem)]">
    <div class="flex shrink-0 items-center gap-3 border-b border-base-300 px-4 py-3">
        <div class="relative h-14 w-14 shrink-0" role="progressbar" aria-valuenow="{{ $coursePct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso corso {{ $coursePct }} percento">
            <svg class="h-14 w-14 -rotate-90" viewBox="0 0 44 44" aria-hidden="true">
                <circle cx="22" cy="22" r="{{ $r }}" fill="none" stroke="currentColor" stroke-width="4" class="text-base-300" />
                <circle cx="22" cy="22" r="{{ $r }}" fill="none" stroke="currentColor" stroke-width="4"
                        class="text-primary transition-[stroke-dashoffset]"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $c }}"
                        stroke-dashoffset="{{ $offset }}" />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[11px] font-bold tabular-nums">{{ $coursePct }}%</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-[11px] uppercase tracking-wide text-base-content/50">Corso</div>
            <div class="truncate text-sm font-semibold leading-snug">{{ $course->title }}</div>
            <div class="mt-0.5 truncate text-xs text-base-content/60">
                In riproduzione: <span class="font-medium text-base-content/80">{{ $currentLesson->title }}</span>
            </div>
            <div class="mt-0.5 text-xs text-base-content/60">
                {{ $completed }} di {{ (int) $totalCount }} lezioni completate
            </div>
            <a href="{{ route('tenant.courses.show', $course) }}"
               class="link link-primary mt-1 inline-flex text-xs font-medium">
                Vai al corso
            </a>
        </div>
    </div>

    <div class="border-b border-base-300 px-4 py-2.5">
        <h2 class="text-sm font-semibold tracking-tight">Contenuti del corso</h2>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto">
        @foreach ($course->modules as $module)
            @php
                $moduleOpen = $currentModuleId !== null && (string) $module->id === (string) $currentModuleId;
                $moduleLessonCount = $module->lessons->count();
                $moduleCompleted = $module->lessons->filter(
                    fn ($l) => $completedLessonIds->contains($l->id)
                )->count();
            @endphp

            <details class="group border-b border-base-300 last:border-b-0" @if ($moduleOpen) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-2 bg-base-200/50 px-4 py-2.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                    <i class="ph ph-caret-right text-base-content/50 transition group-open:rotate-90" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate">{{ $module->title }}</span>
                    <span class="shrink-0 text-[11px] font-normal text-base-content/50">
                        {{ $moduleCompleted }}/{{ $moduleLessonCount }}
                    </span>
                </summary>

                <ul class="divide-y divide-base-200">
                    @foreach ($module->lessons as $lessonItem)
                        @php
                            $isCurrent = $lessonItem->id === $currentLesson->id;
                            $isCompleted = $completedLessonIds->contains($lessonItem->id);
                            $isAccessible = $accessibleLessonIds->contains($lessonItem->id);
                            $durSec = $lessonItem->duration_seconds ?? $lessonItem->videoLesson?->duration_seconds;
                            $durLabel = is_numeric($durSec) ? $formatLessonDuration((int) $durSec) : null;
                            $lt = (string) ($lessonItem->type?->value ?? $lessonItem->type);
                            $typeIcon = match ($lt) {
                                'video' => 'ph-play-circle',
                                'scorm' => 'ph-puzzle-piece',
                                default => 'ph-file-doc',
                            };
                            $pct = (int) ($lessonProgressPct[(string) $lessonItem->id] ?? ($isCompleted ? 100 : 0));
                            if ($isCompleted) {
                                $pct = 100;
                            }
                        @endphp

                        <li>
                            @if ($isAccessible)
                                <a href="{{ route('tenant.lessons.show', [$course, $lessonItem]) }}"
                                   @class([
                                       'flex items-start gap-2.5 border-l-[3px] px-3 py-2.5 text-sm transition',
                                       'border-primary bg-primary/5' => $isCurrent,
                                       'border-transparent hover:bg-base-200/70' => ! $isCurrent,
                                   ])>
                                    <span class="mt-0.5 shrink-0" aria-hidden="true">
                                        @if ($isCompleted)
                                            <i class="ph ph-check-circle text-lg text-success"></i>
                                        @else
                                            <i @class([
                                                'ph text-lg',
                                                $typeIcon,
                                                'text-primary' => $isCurrent,
                                                'text-base-content/45' => ! $isCurrent,
                                            ])></i>
                                        @endif
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start justify-between gap-2">
                                            <span @class([
                                                'block text-[13px] leading-snug',
                                                'font-semibold text-primary' => $isCurrent,
                                                'font-medium text-base-content' => ! $isCurrent && ! $isCompleted,
                                                'font-medium text-base-content/60' => ! $isCurrent && $isCompleted,
                                            ])>
                                                {{ $lessonItem->title }}
                                            </span>
                                            <span class="flex shrink-0 items-center gap-1.5">
                                                @if ($durLabel)
                                                    <span class="font-mono text-[11px] text-base-content/50">{{ $durLabel }}</span>
                                                @endif
                                                @if ($pct > 0)
                                                    <span class="text-[11px] tabular-nums text-base-content/50">{{ $pct }}%</span>
                                                @endif
                                            </span>
                                        </span>
                                        <progress
                                            class="progress mt-1.5 h-1 w-full {{ $isCompleted ? 'progress-success' : 'progress-primary' }}"
                                            value="{{ $pct }}"
                                            max="100"
                                            aria-label="Progresso lezione {{ $pct }} percento"
                                        ></progress>
                                    </span>
                                </a>
                            @else
                                <div class="flex items-start gap-2.5 border-l-[3px] border-transparent px-3 py-2.5 text-sm opacity-60"
                                     title="Completa le lezioni precedenti per sbloccare">
                                    <span class="mt-0.5 shrink-0" aria-hidden="true">
                                        <i class="ph ph-lock-key text-lg text-base-content/40"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="block text-[13px] font-medium leading-snug text-base-content/50">
                                                {{ $lessonItem->title }}
                                            </span>
                                            @if ($durLabel)
                                                <span class="shrink-0 font-mono text-[11px] text-base-content/40">{{ $durLabel }}</span>
                                            @endif
                                        </span>
                                        <progress
                                            class="progress progress-primary mt-1.5 h-1 w-full opacity-40"
                                            value="0"
                                            max="100"
                                            aria-hidden="true"
                                        ></progress>
                                    </span>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endforeach
    </div>
</div>
