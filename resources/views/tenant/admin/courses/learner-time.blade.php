@php
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
        $ua = trim((string) $ua);
        if ($ua === '') {
            return '—';
        }
        if ($ua === 'manual') {
            return 'Manuale';
        }
        $u = strtolower($ua);
        if (str_contains($u, 'edg/')) return 'Edge';
        if (str_contains($u, 'chrome/')) return 'Chrome';
        if (str_contains($u, 'firefox/')) return 'Firefox';
        if (str_contains($u, 'safari/') && ! str_contains($u, 'chrome/')) return 'Safari';
        return 'Browser';
    };
    $creating = old('_form') === 'create';
    $reopenId = old('_form') === 'update' ? (string) old('_session_id') : '';
@endphp

<x-layouts.tenant :title="$course->title.' — Report corsista'">
    <x-ui.page>
        <a href="{{ route('tenant.admin.courses.learners', $course) }}"
           class="link link-hover text-sm text-base-content/70">
            &larr; Corsisti
        </a>

        <x-ui.page-header title="Report corsista" class="mt-2">
            <x-slot:subtitle>
                <span class="font-medium">{{ $enrollment->user?->name ?? '—' }}</span>
                @if ($enrollment->user?->email)
                    <span class="text-base-content/60">· {{ $enrollment->user->email }}</span>
                @endif
                <span class="mt-1 block leading-snug">{{ $course->title }}</span>
            </x-slot:subtitle>
            <x-slot:actions>
                @if ($enrollment->user)
                    <x-admin.impersonate-learner :user="$enrollment->user" />
                @endif
                <a href="{{ route('tenant.admin.courses.learners.report.pdf', [$course, $enrollment]) }}"
                   data-no-loader
                   class="btn btn-outline btn-sm inline-flex items-center gap-2">
                    <i class="ph ph-file-pdf text-base"></i>
                    PDF report
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat title="Sessioni" :value="(int) ($sessionSummary['count'] ?? 0)" />
            <x-ui.stat title="Video (totale)" :value="$fmt((int) ($sessionSummary['video_seconds'] ?? 0))" />
            <x-ui.stat title="SCORM (totale)" :value="$fmt((int) ($sessionSummary['scorm_seconds'] ?? 0))" />
            <x-ui.stat
                title="Totale tempo visto"
                :value="$fmt((int) ($sessionSummary['total_seconds'] ?? 0))"
                class="border border-primary/20 bg-primary/5"
            />
        </div>

        @tenantcan('content.courses.manage')
            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body gap-4 p-4 sm:p-6">
                        <div>
                            <h2 class="text-sm font-semibold">Nuova sessione</h2>
                            <p class="mt-1 text-xs leading-relaxed text-base-content/60">I minuti si sommano al totale visto. Se indichi la lezione e il tempo copre la sua durata, la percentuale del corso avanza. Senza lezione restano solo i minuti.</p>
                        </div>
                        <form class="space-y-3" method="post" action="{{ route('tenant.admin.courses.learners.time.sessions.store', [$course, $enrollment]) }}">
                            @csrf
                            <input type="hidden" name="_form" value="create">
                            @include('tenant.admin.courses.partials.session-form-fields', [
                                'prefix' => 'new',
                                'lessons' => $lessons,
                                'started' => $creating ? old('started_at') : '',
                                'ended' => $creating ? old('ended_at') : '',
                                'videoMinutes' => $creating ? old('video_minutes', 0) : 0,
                                'scormMinutes' => $creating ? old('scorm_minutes', 0) : 0,
                                'lessonId' => $creating ? old('lesson_id') : '',
                            ])
                            <button type="submit" class="btn btn-primary btn-sm">Aggiungi sessione</button>
                        </form>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body gap-4 p-4 sm:p-6">
                        <div>
                            <h2 class="text-sm font-semibold">Importa sessioni da CSV</h2>
                            <p class="mt-1 text-xs leading-relaxed text-base-content/60">
                                Prima riga di intestazione. Separatore virgola o punto e virgola.
                                Colonne: <span class="font-mono">inizio</span>, <span class="font-mono">fine</span>,
                                <span class="font-mono">minuti_video</span> e/o <span class="font-mono">minuti_scorm</span>,
                                <span class="font-mono">lezione</span> (facoltativa, titolo esatto).
                                Date nel formato <span class="font-mono">gg/mm/aaaa hh:mm</span>.
                                Con la lezione indicata, il tempo che ne copre la durata aggiorna anche la percentuale del corso.
                            </p>
                        </div>
                        <a href="{{ route('tenant.admin.courses.learners.time.sessions.template', [$course, $enrollment]) }}"
                           data-no-loader
                           class="link link-primary text-xs font-semibold">
                            Scarica il modello CSV
                        </a>
                        <form class="space-y-3" method="post" enctype="multipart/form-data" action="{{ route('tenant.admin.courses.learners.time.sessions.import', [$course, $enrollment]) }}">
                            @csrf
                            <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt,text/csv" required class="file-input file-input-bordered file-input-sm w-full">
                            <button type="submit" class="btn btn-outline btn-sm">Importa CSV</button>
                        </form>
                        @if (session('import_errors'))
                            <div class="rounded-box border border-warning/40 bg-warning/10 p-3 text-xs">
                                <p class="font-semibold">Righe non importate</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach (session('import_errors') as $importError)
                                        <li>{{ $importError }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endtenantcan

        <div class="card bg-base-100 shadow-lg mt-6 overflow-hidden">
            <div class="border-b border-base-300 px-4 py-4 sm:px-6">
                <h2 class="text-sm font-semibold">Sessioni</h2>
                <p class="mt-1 text-xs text-base-content/60">Ogni riga è un intervallo di visione. Le sessioni manuali e quelle importate compaiono come «Manuale».</p>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full min-w-[760px]">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">Inizio</th>
                            <th class="whitespace-nowrap">Fine</th>
                            <th>Capitolo</th>
                            <th class="whitespace-nowrap">Origine</th>
                            <th class="whitespace-nowrap">IP</th>
                            <th class="whitespace-nowrap text-right">Video</th>
                            <th class="whitespace-nowrap text-right">SCORM</th>
                            <th class="whitespace-nowrap text-right">Totale</th>
                            @tenantcan('content.courses.manage')
                                <th class="whitespace-nowrap text-right">Azioni</th>
                            @endtenantcan
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
                                $editing = $reopenId === (string) $s->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="text-sm font-medium whitespace-nowrap">{{ $start->format('d/m/Y H:i') }}</div>
                                    <div class="text-xs text-base-content/60">{{ $start->locale('it')->isoFormat('dddd') }}</div>
                                </td>
                                <td class="text-sm whitespace-nowrap">{{ $end->format('d/m/Y H:i') }}</td>
                                <td class="max-w-xs text-sm">{{ $chapter !== '' ? $chapter : '—' }}</td>
                                <td class="text-sm">{{ $browser($s->user_agent ?? null) }}</td>
                                <td class="text-sm font-mono">{{ $s->ip_address ?? '—' }}</td>
                                <td class="text-right text-sm tabular-nums">{{ $fmt($video) }}</td>
                                <td class="text-right text-sm tabular-nums">{{ $fmt($scorm) }}</td>
                                <td class="text-right text-sm font-semibold tabular-nums">{{ $fmt($total) }}</td>
                                @tenantcan('content.courses.manage')
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                    class="btn btn-ghost btn-xs"
                                                    onclick="document.getElementById('edit-session-{{ $s->id }}').showModal()">
                                                Modifica
                                            </button>
                                            <form method="post"
                                                  action="{{ route('tenant.admin.courses.learners.time.sessions.destroy', [$course, $enrollment, $s->id]) }}"
                                                  onsubmit="return confirm('Eliminare questa sessione?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-xs text-error">Elimina</button>
                                            </form>
                                        </div>
                                        <dialog id="edit-session-{{ $s->id }}" class="modal text-left">
                                            <div class="modal-box max-w-lg">
                                                <h3 class="text-base font-semibold">Modifica sessione</h3>
                                                <p class="mt-1 text-xs text-base-content/60">{{ $start->format('d/m/Y H:i') }} – {{ $end->format('d/m/Y H:i') }}</p>
                                                <form class="mt-4 space-y-3" method="post" action="{{ route('tenant.admin.courses.learners.time.sessions.update', [$course, $enrollment, $s->id]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="_form" value="update">
                                                    <input type="hidden" name="_session_id" value="{{ $s->id }}">
                                                    @include('tenant.admin.courses.partials.session-form-fields', [
                                                        'prefix' => $s->id,
                                                        'lessons' => $lessons,
                                                        'started' => $editing ? old('started_at') : $start->format('Y-m-d\\TH:i'),
                                                        'ended' => $editing ? old('ended_at') : $end->format('Y-m-d\\TH:i'),
                                                        'videoMinutes' => $editing ? old('video_minutes') : (int) floor($video / 60),
                                                        'scormMinutes' => $editing ? old('scorm_minutes') : (int) floor($scorm / 60),
                                                        'lessonId' => $editing ? old('lesson_id') : ($s->lesson_id ?? ''),
                                                    ])
                                                    <div class="modal-action mt-2">
                                                        <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">Annulla</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Salva</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>Chiudi</button>
                                            </form>
                                        </dialog>
                                    </td>
                                @endtenantcan
                            </tr>
                        @empty
                            <tr>
                                <td class="py-12 text-center" colspan="9">
                                    <div class="mx-auto max-w-md text-sm text-base-content/70">
                                        <p class="font-medium">Nessuna sessione ancora</p>
                                        <p class="mt-2">Aggiungi una sessione, importa un CSV, oppure attendi che il corsista guardi i contenuti del corso.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($errors->any() && $reopenId !== '' && $sessions->contains(fn ($s) => (string) $s->id === $reopenId))
            <script>
                document.getElementById(@json('edit-session-'.$reopenId))?.showModal();
            </script>
        @endif
    </x-ui.page>
</x-layouts.tenant>
