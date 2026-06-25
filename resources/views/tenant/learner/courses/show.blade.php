<x-layouts.tenant :title="$pageTitle ?? $course->title">
    <div class="mx-auto max-w-[1440px] px-4 py-10 lg:px-6">
        @php
            $formatDuration = function (?int $sec) {
                $s = (int) ($sec ?? 0);
                if ($s <= 0) return null;
                $h = intdiv($s, 3600);
                $m = intdiv($s % 3600, 60);
                $ss = $s % 60;
                return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $ss) : sprintf('%d:%02d', $m, $ss);
            };

            $hero = $course->thumbnailPublicUrl();
            $moduleCount = $course->modules->count();
            $requiredTotal = isset($requiredLessonIds) ? (int) $requiredLessonIds->count() : 0;
            $completedRequired = (int) ($requiredCompletedCount ?? 0);
            $progressPct = $enrollment ? (int) min(100, max(0, (float) $enrollment->progress_pct)) : 0;
        @endphp

        <div class="mb-8">
            <a href="{{ route('tenant.dashboard') }}"
               onclick="if (history.length > 1) { history.back(); return false; }"
               class="link link-hover text-sm text-base-content/70">&larr; Torna indietro</a>

            <div class="mt-4 grid gap-6 lg:grid-cols-[420px,1fr] lg:items-start">
                <div class="card bordered bg-base-100 overflow-hidden">
                    @if ($hero)
                        <figure class="relative aspect-[16/9] w-full overflow-hidden bg-base-300">
                            <img src="{{ $hero }}"
                                 alt="{{ $course->title }}"
                                 class="absolute inset-0 h-full w-full object-cover object-top">
                        </figure>
                    @else
                        <div class="flex aspect-[16/9] w-full items-center justify-center bg-base-300">
                            <div class="text-center">
                                <i class="ph ph-graduation-cap text-4xl text-base-content/30" aria-hidden="true"></i>
                                <div class="mt-2 text-xs text-base-content/60">Nessuna cover</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-ghost badge-sm">
                            {{ $moduleCount }} {{ $moduleCount === 1 ? 'modulo' : 'moduli' }}
                        </span>
                        @if ($enrollment)
                            <span class="badge badge-ghost badge-sm">
                                {{ $completedRequired }} di {{ $requiredTotal }} lezioni completate
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ $course->title }}</h1>

                    @if (filled($course->description))
                        <p class="mt-2 max-w-3xl text-sm text-base-content/70">{{ $course->description }}</p>
                    @endif

                    @if ($enrollment)
                        <div class="mt-6 max-w-xl space-y-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-xs text-base-content/70">
                                <span>Il tuo progresso</span>
                                <span class="font-mono font-semibold tabular-nums text-warning">{{ $progressPct }}%</span>
                            </div>
                            <progress class="progress progress-primary w-full"
                                      value="{{ $progressPct }}"
                                      max="100"></progress>
                        </div>

                        @php
                            $nextLesson = null;
                            if (isset($nextLessonId) && $nextLessonId) {
                                $nextLesson = $course->modules->flatMap(fn ($m) => $m->lessons)->firstWhere('id', $nextLessonId);
                            }
                        @endphp

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            @if ($enrollment->status === \App\Enums\EnrollmentStatus::Completed)
                                <a href="{{ route('tenant.courses.certificate', $course) }}"
                                   class="btn btn-outline btn-warning gap-2">
                                    <i class="ph ph-certificate" aria-hidden="true"></i>
                                    Scarica certificato (PDF)
                                </a>
                            @endif
                            @if ($nextLesson)
                                <a href="{{ route('tenant.lessons.show', [$course, $nextLesson]) }}"
                                   class="btn btn-primary gap-2">
                                    <i class="ph ph-play"></i>
                                    {{ $progressPct > 0 ? 'Riprendi corso' : 'Inizia corso' }} &rarr;
                                </a>
                                <span class="text-xs text-base-content/60">
                                    Prossima lezione: <span class="font-medium">{{ $nextLesson->title }}</span>
                                </span>
                            @elseif ($enrollment->status !== \App\Enums\EnrollmentStatus::Completed)
                                <span class="text-sm text-base-content/60">Nessuna lezione disponibile.</span>
                            @endif
                        </div>
                    @else
                        <div class="mt-6 flex flex-wrap items-center gap-4">
                            <form method="post" action="{{ route('tenant.courses.enroll', $course) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary gap-2">
                                    <i class="ph ph-user-plus"></i>
                                    Iscriviti al corso
                                </button>
                            </form>
                            <p class="max-w-md text-xs text-base-content/60">Dopo l’iscrizione potrai aprire tutte le lezioni e il progresso verrà registrato.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($course->modules as $module)
                <section class="card bordered bg-base-100">
                    <div class="border-b border-base-300 px-5 py-4">
                        @php
                            $m = $moduleMeta[$module->id] ?? null;
                            $lessonCount = (int) ($m['lesson_count'] ?? 0);
                            $totalSeconds = (int) ($m['total_seconds'] ?? 0);
                            $totalLabel = $formatDuration($totalSeconds);
                        @endphp
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div class="text-xs uppercase tracking-wider text-base-content/60">
                                Modulo {{ $loop->iteration }} di {{ $course->modules->count() }}
                            </div>
                            <div class="text-xs text-base-content/60">
                                {{ $lessonCount }} {{ $lessonCount === 1 ? 'lezione' : 'lezioni' }}
                                @if ($totalLabel)
                                    · {{ $totalLabel }}
                                @endif
                            </div>
                        </div>
                        <h2 class="mt-1 text-lg font-semibold">{{ $module->title }}</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2">
                            @foreach ($module->lessons as $lesson)
                                @php($lt = (string) ($lesson->type?->value ?? $lesson->type))
                                @php($isRequired = (bool) ($lesson->required ?? false))
                                @php($isCompleted = isset($completedLessonIds) ? $completedLessonIds->contains($lesson->id) : false)
                                @php($isStarted = isset($startedLessonIds) ? $startedLessonIds->contains($lesson->id) : false)
                                @php($durSec = $lesson->duration_seconds ?? $lesson->videoLesson?->duration_seconds)
                                @php($durLabel = $formatDuration(is_numeric($durSec) ? (int) $durSec : null))
                                @php($typeIcon = match ($lt) { 'video' => 'ph-play-circle', 'scorm' => 'ph-puzzle-piece', default => 'ph-file-doc' })
                                @php($isAccessible = isset($accessibleLessonIds) ? $accessibleLessonIds->contains($lesson->id) : true)
                                @if ($enrollment && $isAccessible)
                                    <a href="{{ route('tenant.lessons.show', [$course, $lesson]) }}"
                                       class="flex items-center gap-3 rounded-xl border border-base-300 bg-base-200/50 px-4 py-3 transition hover:border-primary/40 hover:bg-base-200">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-base-300 bg-base-100" aria-hidden="true">
                                            <i class="ph {{ $typeIcon ?? 'ph-file-doc' }} text-2xl text-base-content/70"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium">{{ $lesson->title }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                @if (($isCompleted ?? false))
                                                    <span class="badge badge-success badge-sm">Completata</span>
                                                @elseif (($isStarted ?? false))
                                                    <span class="badge badge-info badge-sm">In corso</span>
                                                @else
                                                    <span class="badge badge-ghost badge-sm">Da fare</span>
                                                @endif

                                                @if (($isRequired ?? false))
                                                    <span class="badge badge-warning badge-sm">Obbligatoria</span>
                                                @endif

                                                @if (($durLabel ?? null))
                                                    <span class="badge badge-ghost badge-sm gap-1">
                                                        <i class="ph ph-clock" aria-hidden="true"></i>
                                                        {{ $durLabel }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <i class="ph ph-caret-right shrink-0 text-base-content/50"></i>
                                    </a>
                                @elseif ($enrollment)
                                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-base-300 bg-base-200/30 px-4 py-3 opacity-80"
                                         title="Completa le lezioni precedenti per sbloccare">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-base-300 bg-base-100" aria-hidden="true">
                                            <i class="ph ph-lock-key text-2xl text-base-content/40"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-base-content/70">{{ $lesson->title }}</div>
                                            <div class="mt-0.5 text-xs text-base-content/60">Completa le lezioni precedenti per sbloccare</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-base-300 bg-base-200/30 px-4 py-3 opacity-80">
                                        <span class="flex h-12 w-20 shrink-0 items-center justify-center rounded-lg border border-base-300 bg-base-100" aria-hidden="true">
                                            <i class="ph ph-lock-key text-2xl text-base-content/40"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-base-content/70">{{ $lesson->title }}</div>
                                            <div class="mt-0.5 text-xs text-base-content/60">Disponibile dopo l’iscrizione</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Nessun modulo in questo corso.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.tenant>
