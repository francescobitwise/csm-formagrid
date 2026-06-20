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
    $browser = function (?string $ua): string {
        $ua = (string) $ua;
        if ($ua === '') return '—';
        $u = strtolower($ua);
        if (str_contains($u, 'edg/')) return 'Edge';
        if (str_contains($u, 'chrome/')) return 'Chrome';
        if (str_contains($u, 'firefox/')) return 'Firefox';
        if (str_contains($u, 'safari/') && ! str_contains($u, 'chrome/')) return 'Safari';
        return 'Browser';
    };
@endphp

<x-layouts.tenant :title="$course->title.' — Dettaglio tempi'">
    <x-ui.page>
        <a href="{{ route('tenant.admin.courses.learners', $course) }}"
           class="link link-hover text-sm text-base-content/70">
            &larr; Corsisti
        </a>

        <x-ui.page-header title="Dettaglio tempi" class="mt-2">
            <x-slot:subtitle>
                <span class="font-medium">{{ $enrollment->user?->name ?? '—' }}</span>
                @if ($enrollment->user?->email)
                    <span class="text-base-content/60">· {{ $enrollment->user->email }}</span>
                @endif
                <span class="text-base-content/50"> · </span>
                <span>{{ $course->title }}</span>
            </x-slot:subtitle>
        </x-ui.page-header>

        @if (($sessionSummary['count'] ?? 0) > 0)
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat
                    title="Sessioni"
                    :value="(int) $sessionSummary['count']"
                />
                <x-ui.stat
                    title="Video (totale)"
                    :value="$fmt((int) $sessionSummary['video_seconds'])"
                />
                <x-ui.stat
                    title="SCORM (totale)"
                    :value="$fmt((int) $sessionSummary['scorm_seconds'])"
                />
                <x-ui.stat
                    title="Totale tempo visto"
                    :value="$fmt((int) $sessionSummary['total_seconds'])"
                    class="border border-primary/20 bg-primary/5"
                />
            </div>
        @endif

        <div class="card bg-base-100 shadow-lg mt-6 overflow-hidden">
            <div class="border-b border-base-300 px-4 py-4 sm:px-6">
                <h2 class="text-sm font-semibold">Sessioni</h2>
                <p class="mt-1 text-xs text-base-content/60">Cronologia per sessione, con modifica inline.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full min-w-[640px]">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">Inizio</th>
                            <th class="whitespace-nowrap">Fine</th>
                            <th class="whitespace-nowrap">Capitolo</th>
                            <th class="whitespace-nowrap">Browser</th>
                            <th class="whitespace-nowrap">IP</th>
                            <th class="whitespace-nowrap text-right">Video</th>
                            <th class="whitespace-nowrap text-right">SCORM</th>
                            <th class="whitespace-nowrap text-right">Totale</th>
                            <th class="whitespace-nowrap text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $s)
                            @php
                                $start = \Illuminate\Support\Carbon::parse($s->started_at);
                                $end = \Illuminate\Support\Carbon::parse($s->ended_at);
                                $video = (int) ($s->video_seconds ?? 0);
                                $scorm = (int) ($s->scorm_seconds ?? 0);
                                $total = (int) ($s->total_seconds ?? 0);
                                $chapter = (string) ($s->lesson_title ?? '');
                            @endphp
                            <tr>
                                <td>
                                    <div class="text-sm font-medium">{{ $start->format('d/m/Y H:i') }}</div>
                                    <div class="text-xs text-base-content/60">{{ $start->locale('it')->isoFormat('dddd') }}</div>
                                </td>
                                <td class="text-sm">
                                    {{ $end->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-sm">
                                    {{ $chapter !== '' ? $chapter : '—' }}
                                </td>
                                <td class="text-sm">{{ $browser($s->user_agent ?? null) }}</td>
                                <td class="text-sm font-mono">{{ $s->ip_address ?? '—' }}</td>
                                <td class="text-right text-sm tabular-nums">{{ $fmt($video) }}</td>
                                <td class="text-right text-sm tabular-nums">{{ $fmt($scorm) }}</td>
                                <td class="text-right text-sm font-semibold tabular-nums">{{ $fmt($total) }}</td>
                                <td class="relative text-right">
                                    <details class="group/edit inline-block text-left">
                                        <summary class="link link-primary cursor-pointer list-none text-xs font-semibold [&::-webkit-details-marker]:hidden">
                                            Modifica
                                        </summary>
                                        <div class="absolute end-0 top-full z-50 mt-2 w-[min(calc(100vw-2rem),22rem)] rounded-box border border-base-300 bg-base-100 p-4 shadow-xl">
                                            <form class="space-y-3" method="post" action="{{ route('tenant.admin.courses.learners.time.sessions.update', [$course, $enrollment, $s->id]) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="form-control w-full">
                                                    <label class="label py-1" for="started_at_{{ $s->id }}">
                                                        <span class="label-text text-[11px] font-semibold uppercase tracking-wider">Inizio</span>
                                                    </label>
                                                    <input id="started_at_{{ $s->id }}" type="datetime-local" name="started_at"
                                                           value="{{ old('started_at') ?: $start->format('Y-m-d\\TH:i') }}"
                                                           class="input input-bordered w-full input-sm">
                                                </div>
                                                <div class="form-control w-full">
                                                    <label class="label py-1" for="ended_at_{{ $s->id }}">
                                                        <span class="label-text text-[11px] font-semibold uppercase tracking-wider">Fine</span>
                                                    </label>
                                                    <input id="ended_at_{{ $s->id }}" type="datetime-local" name="ended_at"
                                                           value="{{ old('ended_at') ?: $end->format('Y-m-d\\TH:i') }}"
                                                           class="input input-bordered w-full input-sm">
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="form-control w-full">
                                                        <label class="label py-1" for="video_minutes_{{ $s->id }}">
                                                            <span class="label-text text-[11px] font-semibold uppercase tracking-wider">Video (min)</span>
                                                        </label>
                                                        <input id="video_minutes_{{ $s->id }}" type="number" min="0" step="1" name="video_minutes"
                                                               value="{{ old('video_minutes') ?: (int) floor($video / 60) }}"
                                                               class="input input-bordered w-full input-sm">
                                                    </div>
                                                    <div class="form-control w-full">
                                                        <label class="label py-1" for="scorm_minutes_{{ $s->id }}">
                                                            <span class="label-text text-[11px] font-semibold uppercase tracking-wider">SCORM (min)</span>
                                                        </label>
                                                        <input id="scorm_minutes_{{ $s->id }}" type="number" min="0" step="1" name="scorm_minutes"
                                                               value="{{ old('scorm_minutes') ?: (int) floor($scorm / 60) }}"
                                                               class="input input-bordered w-full input-sm">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm w-full">Salva</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-12 text-center" colspan="9">
                                    <div class="mx-auto max-w-md text-sm text-base-content/70">
                                        <p class="font-medium">Nessuna sessione ancora</p>
                                        <p class="mt-2">Le sessioni compaiono quando il corsista guarda contenuti del corso (video o SCORM) dopo l’attivazione del tracciamento.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
