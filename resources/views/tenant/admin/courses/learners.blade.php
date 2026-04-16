<x-layouts.tenant :title="$course->title.' — Corsisti'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <a href="{{ route('tenant.admin.courses.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Corsi</a>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">{{ $course->title }}</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        Corsisti iscritti: minuti visti = somma sessioni (<span class="font-mono text-slate-500">watch_time_sessions</span>), completamento da progressi lezione. “Sta guardando” = attività (video o SCORM) negli ultimi {{ (int) $activeWithinSeconds }}s.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 text-xs text-slate-400">
                    <a href="{{ route('tenant.admin.courses.companies-report', $course) }}"
                       class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-2 text-xs">
                        <i class="ph ph-buildings text-base"></i>
                        Report aziende
                    </a>
                    <a href="{{ route('tenant.admin.courses.learners.pdf', $course) }}"
                       data-no-loader
                       class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-2 text-xs">
                        <i class="ph ph-file-pdf text-base"></i>
                        Esporta PDF ore corso
                    </a>
                    @php($v = (string) ($course->status?->value ?? $course->status))
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium
                        @if($v==='published') border-lime-500/20 bg-lime-500/10 text-lime-400
                        @elseif($v==='draft') border-amber-500/20 bg-amber-500/10 text-amber-400
                        @else border-slate-500/20 bg-slate-500/10 text-slate-400 @endif">
                        {{ \App\Enums\CourseStatus::tryFrom($v)?->label() ?? $v }}
                    </span>
                    <span class="font-mono">{{ $course->slug }}</span>
                </div>
            </div>

            <div class="mt-6 glass-card overflow-hidden rounded-xl border border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-white/5 bg-slate-900/80">
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Corsista</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Minuti visti</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Completamento</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Stato</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Ultima attività</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($enrollments as $enrollment)
                                <tr class="transition-colors hover:bg-white/[0.02]">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-white">{{ $enrollment->user?->name ?? '—' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $enrollment->user?->email ?? '' }}</div>
                                        <div class="mt-2">
                                            <a class="text-xs font-semibold text-sky-400 underline decoration-sky-400/50 underline-offset-2 hover:text-sky-300 hover:decoration-sky-300"
                                               href="{{ route('tenant.admin.courses.learners.time', [$course, $enrollment]) }}">
                                                Dettaglio tempi &rarr;
                                            </a>
                                        </div>
                                        @if ($enrollment->is_watching_now)
                                            <div class="mt-2 inline-flex items-center gap-1 rounded-full border border-lime-500/20 bg-lime-500/10 px-2 py-0.5 text-[10px] font-medium text-lime-300">
                                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-lime-400"></span>
                                                Sta guardando
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-white">{{ (int) ($enrollment->minutes_watched ?? 0) }}</div>
                                        <div class="mt-1 text-xs text-slate-500">min</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php($pct = (float) ($enrollment->progress_pct ?? 0))
                                        @php($pctClamped = (int) min(100, max(0, $pct)))
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-40 overflow-hidden rounded-full bg-white/10">
                                                <div class="h-full rounded-full bg-brand-amber" @style(['width: '.$pctClamped.'%'])></div>
                                            </div>
                                            <div class="text-sm text-white">{{ number_format($pct, 0) }}%</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php($s = (string) ($enrollment->status?->value ?? $enrollment->status))
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium
                                            @if($s==='completed') border-lime-500/20 bg-lime-500/10 text-lime-400
                                            @elseif($s==='active') border-sky-500/20 bg-sky-500/10 text-sky-300
                                            @else border-slate-500/20 bg-slate-500/10 text-slate-400 @endif">
                                            {{ $s }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if ($enrollment->last_activity_at)
                                            <div class="text-sm text-white">{{ $enrollment->last_activity_at->format('d/m/Y H:i') }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $enrollment->last_activity_at->diffForHumans() }}</div>
                                        @else
                                            <div class="text-sm text-slate-500">—</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-10 text-sm text-slate-400" colspan="5">
                                        Nessun corsista iscritto a questo corso.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-white/5 bg-slate-900/30 px-6 py-4">
                    {{ $enrollments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>

