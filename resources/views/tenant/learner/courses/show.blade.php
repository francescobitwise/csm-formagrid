<x-layouts.tenant :title="$pageTitle ?? $course->title">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
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
               class="text-sm text-slate-400 hover:text-white">&larr; Torna indietro</a>

            <div class="mt-4 grid gap-6 lg:grid-cols-[420px,1fr] lg:items-start">
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/50">
                    @if ($hero)
                        <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-900/40">
                            <img src="{{ $hero }}"
                                 alt="{{ $course->title }}"
                                 class="absolute inset-0 h-full w-full object-cover object-top">
                        </div>
                    @else
                        <div class="flex aspect-[16/9] w-full items-center justify-center bg-gradient-to-br from-slate-900/80 to-slate-950/40">
                            <div class="text-center">
                                <i class="ph ph-graduation-cap text-4xl text-slate-600" aria-hidden="true"></i>
                                <div class="mt-2 text-xs text-slate-500">Nessuna cover</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5">
                            {{ $moduleCount }} {{ $moduleCount === 1 ? 'modulo' : 'moduli' }}
                        </span>
                        @if ($enrollment)
                            <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5">
                                {{ $completedRequired }} di {{ $requiredTotal }} lezioni completate
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-white">{{ $course->title }}</h1>

                    @if (filled($course->description))
                        <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">{{ $course->description }}</p>
                    @endif

            @if ($enrollment)
                {{-- Track: non usare dark:bg-slate-800 qui: in light mode app.css matcha [class*='bg-slate-800'] e forza sfondo chiaro (!important), rendendo la barra invisibile. --}}
                <div class="mt-6 max-w-xl space-y-1">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                        <span>Il tuo progresso</span>
                        <span class="font-mono font-semibold tabular-nums text-brand-amber">{{ $progressPct }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-950">
                        <div class="h-full rounded-full bg-brand-blue transition-all"
                             style="width: <?php echo $progressPct; ?>%;"></div>
                    </div>
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
                           class="inline-flex items-center gap-2 rounded-xl border border-brand-amber/45 bg-brand-amber/10 px-6 py-3 text-sm font-semibold text-brand-amber transition hover:bg-brand-amber/15 active:scale-95">
                            <i class="ph ph-certificate" aria-hidden="true"></i>
                            Scarica certificato (PDF)
                        </a>
                    @endif
                    @if ($nextLesson)
                        <a href="{{ route('tenant.lessons.show', [$course, $nextLesson]) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-brand-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                            <i class="ph ph-play"></i>
                            {{ $progressPct > 0 ? 'Riprendi corso' : 'Inizia corso' }} &rarr;
                        </a>
                        <span class="text-xs text-slate-500">
                            Prossima lezione: <span class="text-slate-300">{{ $nextLesson->title }}</span>
                        </span>
                    @elseif ($enrollment->status !== \App\Enums\EnrollmentStatus::Completed)
                        <span class="text-sm text-slate-500">Nessuna lezione disponibile.</span>
                    @endif
                </div>
            @else
                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <form method="post" action="{{ route('tenant.courses.enroll', $course) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-blue px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                            <i class="ph ph-user-plus"></i>
                            Iscriviti al corso
                        </button>
                    </form>
                    <p class="max-w-md text-xs text-slate-500">Dopo l’iscrizione potrai aprire tutte le lezioni e il progresso verrà registrato.</p>
                </div>
            @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($course->modules as $module)
                <section class="glass-card rounded-2xl border border-white/5">
                    <div class="border-b border-white/5 px-5 py-4">
                        @php
                            $m = $moduleMeta[$module->id] ?? null;
                            $lessonCount = (int) ($m['lesson_count'] ?? 0);
                            $totalSeconds = (int) ($m['total_seconds'] ?? 0);
                            $totalLabel = $formatDuration($totalSeconds);
                        @endphp
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div class="text-xs uppercase tracking-wider text-slate-500">
                                Modulo {{ $loop->iteration }} di {{ $course->modules->count() }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $lessonCount }} {{ $lessonCount === 1 ? 'lezione' : 'lezioni' }}
                                @if ($totalLabel)
                                    · {{ $totalLabel }}
                                @endif
                            </div>
                        </div>
                        <h2 class="mt-1 text-lg font-semibold text-white">{{ $module->title }}</h2>
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
                                @if ($enrollment)
                                    <a href="{{ route('tenant.lessons.show', [$course, $lesson]) }}"
                                       class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-800/40 px-4 py-3 transition hover:border-brand-blue/25 dark:bg-slate-800/40">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-slate-900/70" aria-hidden="true">
                                            <i class="ph {{ $typeIcon ?? 'ph-file-doc' }} text-2xl text-slate-300"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-white">{{ $lesson->title }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                @if (($isCompleted ?? false))
                                                    <span class="rounded-full border border-lime-500/20 bg-lime-500/10 px-2 py-0.5 text-[11px] font-semibold text-lime-300">Completata</span>
                                                @elseif (($isStarted ?? false))
                                                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[11px] font-semibold text-sky-200">In corso</span>
                                                @else
                                                    <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-slate-300">Da fare</span>
                                                @endif

                                                @if (($isRequired ?? false))
                                                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-200">Obbligatoria</span>
                                                @endif

                                                @if (($durLabel ?? null))
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-slate-300">
                                                        <i class="ph ph-clock" aria-hidden="true"></i>
                                                        {{ $durLabel }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <i class="ph ph-caret-right shrink-0 text-slate-500"></i>
                                    </a>
                                @else
                                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-white/10 bg-slate-800/40 px-4 py-3 opacity-80 dark:bg-slate-800/40">
                                        <span class="flex h-12 w-20 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-slate-900/80" aria-hidden="true">
                                            <i class="ph ph-lock-key text-2xl text-slate-600"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-slate-300">{{ $lesson->title }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500">Disponibile dopo l’iscrizione</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <div class="glass-card rounded-2xl border border-white/5 p-8 text-sm text-slate-400">
                    Nessun modulo in questo corso.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.tenant>
