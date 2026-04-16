@php
    $gapMin = intdiv((int) ($sessionGapSeconds ?? 1800), 60);
    $fmt = function (int $sec): string {
        $sign = $sec < 0 ? '-' : '';
        $sec = abs($sec);
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        if ($h > 0) {
            return $sign.$h.'h '.$m.'m';
        }
        return $sign.$m.'m';
    };
@endphp

<x-layouts.tenant :title="$course->title.' — Dettaglio tempi'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="flex flex-col gap-2">
                <a href="{{ route('tenant.admin.courses.learners', $course) }}"
                   class="text-sm text-slate-500 hover:text-sky-500 dark:text-slate-400 dark:hover:text-sky-400">
                    &larr; Corsisti
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dettaglio tempi</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $enrollment->user?->name ?? '—' }}</span>
                        @if ($enrollment->user?->email)
                            <span class="text-slate-500 dark:text-slate-500">· {{ $enrollment->user->email }}</span>
                        @endif
                        <span class="text-slate-400 dark:text-slate-600"> · </span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $course->title }}</span>
                    </p>
                </div>
            </div>

            @if (($sessionSummary['count'] ?? 0) > 0)
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-white/10 bg-white/70 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sessioni</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ (int) $sessionSummary['count'] }}</div>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/70 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Video (totale)</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $fmt((int) $sessionSummary['video_seconds']) }}</div>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/70 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">SCORM (totale)</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $fmt((int) $sessionSummary['scorm_seconds']) }}</div>
                    </div>
                    <div class="rounded-xl border border-brand-blue/20 bg-brand-blue/5 px-4 py-3 shadow-sm dark:border-brand-blue/30 dark:bg-brand-blue/10">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Totale tempo visto</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $fmt((int) $sessionSummary['total_seconds']) }}</div>
                    </div>
                </div>
            @endif

            <div class="mt-6 glass-card overflow-hidden rounded-xl border border-white/5">
                <div class="border-b border-white/5 bg-slate-900/40 px-4 py-4 sm:px-6 dark:bg-slate-900/60">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Sessioni</h2>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Cronologia per sessione, con modifica inline.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50 dark:border-white/5 dark:bg-slate-900/80">
                                <th class="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6">Inizio</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6">Fine</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6">Video</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6">SCORM</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6">Totale</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:px-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-white/5">
                            @forelse ($sessions as $s)
                                @php
                                    $start = \Illuminate\Support\Carbon::parse($s->started_at);
                                    $end = \Illuminate\Support\Carbon::parse($s->ended_at);
                                    $video = (int) ($s->video_seconds ?? 0);
                                    $scorm = (int) ($s->scorm_seconds ?? 0);
                                    $total = (int) ($s->total_seconds ?? 0);
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/90 dark:hover:bg-white/[0.03]">
                                    <td class="px-4 py-3 sm:px-6">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $start->format('d/m/Y H:i') }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-500">{{ $start->locale('it')->isoFormat('dddd') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-white sm:px-6">
                                        {{ $end->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-800 dark:text-white sm:px-6">{{ $fmt($video) }}</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-800 dark:text-white sm:px-6">{{ $fmt($scorm) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold tabular-nums text-slate-900 dark:text-white sm:px-6">{{ $fmt($total) }}</td>
                                    <td class="relative px-4 py-3 text-right sm:px-6">
                                        <details class="group/edit inline-block text-left">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-sky-600 underline decoration-sky-600/40 underline-offset-2 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300 [&::-webkit-details-marker]:hidden">
                                                Modifica
                                            </summary>
                                            <div class="absolute end-0 top-full z-50 mt-2 w-[min(calc(100vw-2rem),22rem)] rounded-xl border border-slate-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-slate-950">
                                                <form class="space-y-3" method="post" action="{{ route('tenant.admin.courses.learners.time.sessions.update', [$course, $enrollment, $s->id]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400" for="started_at_{{ $s->id }}">Inizio</label>
                                                        <input id="started_at_{{ $s->id }}" type="datetime-local" name="started_at"
                                                               value="{{ old('started_at') ?: $start->format('Y-m-d\\TH:i') }}"
                                                               class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 dark:border-white/10 dark:bg-slate-900/60 dark:text-white">
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400" for="ended_at_{{ $s->id }}">Fine</label>
                                                        <input id="ended_at_{{ $s->id }}" type="datetime-local" name="ended_at"
                                                               value="{{ old('ended_at') ?: $end->format('Y-m-d\\TH:i') }}"
                                                               class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 dark:border-white/10 dark:bg-slate-900/60 dark:text-white">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400" for="video_minutes_{{ $s->id }}">Video (min)</label>
                                                            <input id="video_minutes_{{ $s->id }}" type="number" min="0" step="1" name="video_minutes"
                                                                   value="{{ old('video_minutes') ?: (int) floor($video / 60) }}"
                                                                   class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 dark:border-white/10 dark:bg-slate-900/60 dark:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400" for="scorm_minutes_{{ $s->id }}">SCORM (min)</label>
                                                            <input id="scorm_minutes_{{ $s->id }}" type="number" min="0" step="1" name="scorm_minutes"
                                                                   value="{{ old('scorm_minutes') ?: (int) floor($scorm / 60) }}"
                                                                   class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 dark:border-white/10 dark:bg-slate-900/60 dark:text-white">
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="admin-btn-primary w-full justify-center px-4 py-2 text-sm">Salva</button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-12 text-center sm:px-6" colspan="6">
                                        <div class="mx-auto max-w-md text-sm text-slate-600 dark:text-slate-400">
                                            <p class="font-medium text-slate-800 dark:text-slate-200">Nessuna sessione ancora</p>
                                            <p class="mt-2">Le sessioni compaiono quando il corsista guarda contenuti del corso (video o SCORM) dopo l’attivazione del tracciamento.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
