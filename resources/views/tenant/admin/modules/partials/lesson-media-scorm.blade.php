@php
    $sStatus = (string) ($scorm?->status?->value ?? $scorm?->status ?? 'processing');
    $launchUrl = $scorm?->launchUrl() ?: '';
    $hasPackage = $scorm && (string) ($scorm->s3_path ?? '') !== '';
@endphp

<div class="mt-3 space-y-4">
    <div class="lesson-dropzone" data-upload-root>
        <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-base-200/80 text-accent">
                <i class="ph ph-puzzle-piece text-2xl" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-base-content">
                    {{ $hasPackage ? 'Sostituisci pacchetto SCORM' : 'Carica pacchetto SCORM' }}
                </p>
                <p class="mt-0.5 text-xs text-base-content/60">
                    Solo file <span class="font-mono text-base-content/80">.zip</span> — versione e launch rilevati in automatico.
                </p>
                @if ($hasPackage)
                    <p class="mt-2 truncate font-mono text-[11px] text-base-content/50" title="{{ $scorm->s3_path }}">
                        {{ $scorm->s3_path }}
                    </p>
                @endif
            </div>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
            action="{{ route('tenant.admin.modules.lessons.scorm.upload', [$module, $lesson]) }}"
            class="mt-4 space-y-2"
            data-upload-form
            data-no-loader
        >
            @csrf
            <input
                type="file"
                name="scorm_file"
                accept=".zip,application/zip,application/x-zip-compressed"
                class="file-input file-input-bordered file-input-sm w-full bg-base-100"
                required
            >
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    {{ $hasPackage ? 'Sostituisci' : 'Carica SCORM' }}
                </button>
            </div>
            <div class="hidden" data-upload-progress hidden>
                <progress class="progress progress-primary w-full" value="0" max="100" data-upload-progress-bar></progress>
                <p class="mt-1 text-xs tabular-nums text-base-content/60" data-upload-progress-label>0%</p>
            </div>
            <p class="min-h-[1rem] text-xs text-base-content/60" data-upload-status></p>
        </form>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @include('tenant.admin.modules.partials.status-pill', ['status' => $sStatus, 'lessonId' => $lesson->id])
        <span class="text-xs text-base-content/60">
            @if ($sStatus === 'ready')
                Pacchetto pronto.
            @elseif ($sStatus === 'error')
                Elaborazione non riuscita.
            @else
                In elaborazione…
            @endif
        </span>
        @if ($sStatus === 'error')
            <form method="post" action="{{ route('tenant.admin.modules.lessons.scorm.retry', [$module, $lesson]) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs text-warning gap-1" title="Riprova a processare il pacchetto SCORM">
                    <i class="ph ph-arrows-clockwise text-sm" aria-hidden="true"></i>
                    Riprova
                </button>
            </form>
        @endif
        @if ($sStatus === 'ready' && $launchUrl !== '')
            <a href="{{ $launchUrl }}" target="_blank" rel="noreferrer" class="btn btn-ghost btn-xs gap-1">
                <i class="ph ph-arrow-square-out text-sm" aria-hidden="true"></i>
                Apri launch
            </a>
        @endif
    </div>

    <details class="group/adv">
        <summary class="cursor-pointer list-none text-xs font-medium text-base-content/50 transition hover:text-base-content/80 marker:content-none">
            Avanzate
            <span class="ml-1 opacity-70 group-open/adv:hidden">· impostazioni tecniche</span>
        </summary>
        <form method="post" action="{{ route('tenant.admin.modules.lessons.scorm.update', [$module, $lesson]) }}" class="mt-3 space-y-3 rounded-xl bg-base-100 p-4 ring-1 ring-base-300/40">
            @csrf
            @method('put')
            <p class="text-[11px] text-base-content/60">Usa solo per interventi di supporto.</p>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="label py-1" for="scorm_s3_path_{{ $lesson->id }}"><span class="label-text text-xs">Launch path</span></label>
                    <input id="scorm_s3_path_{{ $lesson->id }}" name="s3_path" value="{{ old('s3_path', $scorm?->s3_path) }}" class="input input-bordered input-sm w-full bg-base-100 font-mono text-xs" placeholder="es. tenants/.../index.html">
                </div>
                <div>
                    <label class="label py-1" for="scorm_version_{{ $lesson->id }}"><span class="label-text text-xs">Versione</span></label>
                    <select id="scorm_version_{{ $lesson->id }}" name="version" class="select select-bordered select-sm w-full bg-base-100 font-mono text-xs">
                        @foreach (['1.2', '2004'] as $version)
                            <option value="{{ $version }}" @selected(old('version', ($scorm?->version?->value ?? $scorm?->version ?? '1.2')) === $version)>{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label py-1" for="scorm_status_{{ $lesson->id }}"><span class="label-text text-xs">Forza stato</span></label>
                    <select id="scorm_status_{{ $lesson->id }}" name="status" class="select select-bordered select-sm w-full bg-base-100 font-mono text-xs">
                        @foreach (['processing', 'ready', 'error'] as $status)
                            <option value="{{ $status }}" @selected(old('status', ($scorm?->status?->value ?? $scorm?->status ?? 'processing')) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Salva impostazioni SCORM</button>
        </form>
    </details>
</div>
