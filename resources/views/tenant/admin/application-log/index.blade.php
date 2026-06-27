<x-layouts.tenant :title="'Log applicazione — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Log applicazione"
            subtitle="Ultime righe di storage/logs/laravel.log (solo lettura). Utile per errori SCORM, code, upload e permessi S3."
        />

        <form method="get" action="{{ route('tenant.admin.application-log.index') }}" class="card bordered bg-base-100 mb-6">
            <div class="card-body grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="form-control xl:col-span-2">
                    <label for="file" class="label py-1">
                        <span class="label-text text-xs">File di log</span>
                    </label>
                    <select id="file" name="file" class="select select-bordered w-full font-mono text-xs">
                        @forelse ($logFiles as $logFile)
                            <option value="{{ $logFile }}" @selected($selectedFile === $logFile)>{{ $logFile }}</option>
                        @empty
                            <option value="laravel.log">laravel.log (non trovato)</option>
                        @endforelse
                    </select>
                </div>

                <div class="form-control">
                    <label for="level" class="label py-1">
                        <span class="label-text text-xs">Livello</span>
                    </label>
                    <select id="level" name="level" class="select select-bordered w-full text-sm">
                        <option value="">Tutti</option>
                        @foreach (['error', 'warning', 'info', 'debug', 'critical', 'notice'] as $lvl)
                            <option value="{{ $lvl }}" @selected($level === $lvl)>{{ strtoupper($lvl) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label for="lines" class="label py-1">
                        <span class="label-text text-xs">Righe (max {{ \App\Support\ApplicationLogReader::MAX_LINES }})</span>
                    </label>
                    <input id="lines" type="number" name="lines" min="50" max="{{ \App\Support\ApplicationLogReader::MAX_LINES }}"
                           value="{{ (int) $lines }}" class="input input-bordered w-full font-mono text-sm">
                </div>

                <div class="form-control xl:col-span-2">
                    <label for="q" class="label py-1">
                        <span class="label-text text-xs">Cerca nel testo</span>
                    </label>
                    <input id="q" name="q" value="{{ $q }}" type="search" placeholder="SCORM, queue, AccessDenied…"
                           class="input input-bordered w-full font-mono text-sm">
                </div>

                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-5">
                    <button type="submit" class="btn btn-primary btn-sm">Aggiorna</button>
                    @if ($q !== '' || $level !== '' || (int) $lines !== \App\Support\ApplicationLogReader::DEFAULT_LINES)
                        <a href="{{ route('tenant.admin.application-log.index', ['file' => $selectedFile]) }}" class="btn btn-ghost btn-sm">Reset filtri</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-base-content/60">
            @if ($fileSize !== null)
                <span>Dimensione file: {{ number_format($fileSize / 1024, 1, ',', '.') }} KB</span>
            @endif
            @if ($fileModifiedAt)
                <span>Ultima modifica: {{ \Illuminate\Support\Carbon::createFromTimestamp($fileModifiedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span>
            @endif
            <span>Righe mostrate: {{ (int) $matchedCount }}</span>
            @if ($truncated)
                <span class="badge badge-warning badge-sm">Risultati troncati — aumenta il numero di righe o affina la ricerca</span>
            @endif
        </div>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="border-b border-base-300 bg-base-200 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-base-content/60">
                {{ $selectedFile }}
            </div>
            @if ($logLines === [])
                <div class="p-8 text-center text-sm text-base-content/60">
                    Nessuna riga da mostrare. Il file potrebbe non esistere, essere vuoto o non corrispondere ai filtri.
                </div>
            @else
                <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-words bg-neutral p-4 text-xs leading-relaxed text-neutral-content"><code>@foreach ($logLines as $line){{ $line }}
@endforeach</code></pre>
            @endif
        </div>
    </x-ui.page>
</x-layouts.tenant>
