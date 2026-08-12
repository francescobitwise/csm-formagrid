@php
    $vStatus = (string) ($video?->status?->value ?? $video?->status ?? 'processing');
    $readyManifestUrl = $vStatus === 'ready' ? ($video?->hlsManifestUrl() ?: null) : null;
    $hasReadyVideo = $readyManifestUrl !== null;
@endphp

<div class="mt-3 space-y-4">
    @if ($hasReadyVideo)
        <div class="overflow-hidden rounded-2xl bg-base-100 p-2 ring-1 ring-base-300/40">
            <video
                class="video-js vjs-big-play-centered w-full overflow-hidden rounded-xl"
                playsinline
                data-videojs="1"
                @if ($poster = $video?->posterPublicUrl()) poster="{{ $poster }}" @endif
            >
                <source src="{{ $readyManifestUrl }}" type="application/x-mpegURL">
                <track kind="captions" srclang="it" label="Italiano" src="{{ asset('brand/empty-captions.vtt') }}" default>
            </video>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-base-content/60">
            <span>Anteprima streaming HLS</span>
            <a href="{{ $readyManifestUrl }}" target="_blank" rel="noreferrer" class="font-medium text-primary hover:underline">
                Apri streaming
            </a>
        </div>
    @endif

    <div
        class="lesson-dropzone"
        data-upload-root
        data-video-direct-upload
        data-presign-url="{{ route('api.video.presigned-upload') }}"
        data-finalize-url="{{ route('api.video.finalize-upload') }}"
        data-module-id="{{ $module->id }}"
        data-lesson-id="{{ $lesson->id }}"
    >
        <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-base-200/80 text-primary">
                <i class="ph ph-video-camera text-2xl" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-base-content">
                    {{ $hasReadyVideo ? 'Sostituisci video' : 'Carica video' }}
                </p>
                <p class="mt-0.5 text-xs text-base-content/60">
                    MP4 o HLS (.m3u8). Dopo il caricamento la conversione parte in automatico.
                </p>
            </div>
        </div>
        <div class="mt-4 space-y-2">
            <input
                type="file"
                data-direct-file
                accept=".mp4,.m3u8,video/mp4,application/vnd.apple.mpegurl,application/x-mpegURL"
                class="file-input file-input-bordered file-input-sm w-full bg-base-100"
            >
            <div class="flex justify-end">
                <button type="button" data-direct-submit class="btn btn-primary btn-sm">
                    {{ $hasReadyVideo ? 'Carica nuovo' : 'Carica video' }}
                </button>
            </div>
        </div>
        <div class="mt-3 hidden" data-upload-progress hidden>
            <progress class="progress progress-primary w-full" value="0" max="100" data-upload-progress-bar></progress>
            <p class="mt-1 text-xs tabular-nums text-base-content/60" data-upload-progress-label>0%</p>
        </div>
        <p class="mt-2 min-h-[1rem] text-xs text-base-content/60" data-direct-status></p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @include('tenant.admin.modules.partials.status-pill', ['status' => $vStatus, 'lessonId' => $lesson->id])
        <span class="text-xs text-base-content/60">
            @if ($vStatus === 'ready')
                Conversione completata.
            @elseif ($vStatus === 'error')
                Conversione non riuscita.
            @elseif (($video?->original_s3 ?? '') !== '')
                Upload ricevuto; conversione in corso.
            @else
                In attesa di upload.
            @endif
        </span>
        @if ($vStatus === 'error')
            <form method="post" action="{{ route('tenant.admin.modules.lessons.video.retry', [$module, $lesson]) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs text-warning gap-1" title="Riprova a processare il video">
                    <i class="ph ph-arrows-clockwise text-sm" aria-hidden="true"></i>
                    Riprova
                </button>
            </form>
        @endif
    </div>

    <details class="group/adv">
        <summary class="cursor-pointer list-none text-xs font-medium text-base-content/50 transition hover:text-base-content/80 marker:content-none">
            Avanzate
            <span class="ml-1 opacity-70 group-open/adv:hidden">· impostazioni tecniche</span>
        </summary>
        <form method="post" action="{{ route('tenant.admin.modules.lessons.video.update', [$module, $lesson]) }}" class="mt-3 space-y-3 rounded-xl bg-base-100 p-4 ring-1 ring-base-300/40">
            @csrf
            @method('put')
            <p class="text-[11px] text-base-content/60">
                Di norma non servono: il manifest HLS e il file sorgente sono gestiti dal sistema.
            </p>
            @if ($video?->hls_manifest)
                <div class="rounded-lg bg-base-200/60 px-3 py-2 text-[11px] text-base-content/70">
                    <p class="font-medium text-base-content/80">Streaming HLS</p>
                    <p class="mt-1 break-all font-mono">Chiave: {{ $video->hls_manifest }}</p>
                    <p class="mt-1 break-all font-mono text-primary/90">URL: {{ $video->hlsManifestUrl() }}</p>
                </div>
            @endif
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="label py-1" for="manual_status_{{ $lesson->id }}"><span class="label-text text-xs">Forza stato elaborazione</span></label>
                    <select id="manual_status_{{ $lesson->id }}" name="manual_status" class="select select-bordered select-sm w-full bg-base-100 font-mono text-xs">
                        <option value="" @selected(old('manual_status', '') === '')>— non modificare —</option>
                        @foreach (['processing', 'ready', 'error'] as $st)
                            <option value="{{ $st }}" @selected(old('manual_status', '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label py-1" for="original_s3_{{ $lesson->id }}"><span class="label-text text-xs">Percorso sorgente</span></label>
                    <input id="original_s3_{{ $lesson->id }}" name="original_s3" value="{{ old('original_s3', $video?->original_s3) }}" class="input input-bordered input-sm w-full bg-base-100 font-mono text-xs" placeholder="opzionale">
                </div>
                <div>
                    <label class="label py-1" for="hls_manifest_{{ $lesson->id }}"><span class="label-text text-xs">Chiave manifest HLS</span></label>
                    <input id="hls_manifest_{{ $lesson->id }}" name="hls_manifest" value="{{ old('hls_manifest', $video?->hls_manifest) }}" class="input input-bordered input-sm w-full bg-base-100 font-mono text-xs" placeholder="es. tenants/.../master.m3u8">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Salva impostazioni tecniche</button>
        </form>
    </details>
</div>
