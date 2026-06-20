<x-layouts.tenant :title="$module->title.' — Lezioni'">
    <div class="mx-auto max-w-[1320px] px-6 py-10" data-content-status-url="{{ route('tenant.admin.modules.lessons.content-status', $module) }}">
        <x-ui.page>
            <x-ui.flash />

            <x-ui.page-header
                :title="$module->title"
                subtitle="Lezioni e contenuti del modulo. Per usarlo in un corso, associa il modulo dal Moduli del corso."
            >
                <x-slot:actions>
                    <a href="{{ route('tenant.admin.modules.edit', $module) }}" class="btn btn-outline">
                        Impostazioni modulo
                    </a>
                    <a href="{{ route('tenant.admin.modules.index') }}" class="btn btn-outline">
                        Tutti i moduli
                    </a>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="mb-6">
                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-base-content/70">
                    <span class="badge badge-primary badge-outline gap-2 px-3 py-3 text-xs">
                        <i class="ph ph-timer text-base text-accent" aria-hidden="true"></i>
                        <span>Durata totale modulo</span>
                        <span class="font-mono text-sm font-semibold tabular-nums">
                            @if (($moduleLessonDurationCount ?? 0) > 0)
                                {{ \App\Support\DurationFormat::secondsToMmss($moduleTotalDurationSeconds ?? 0) }}
                            @else
                                <span class="font-normal text-base-content/50">non indicata</span>
                            @endif
                        </span>
                    </span>
                    <span class="text-xs text-base-content/60">Somma delle durate indicate per ogni lezione (campo &quot;Durata&quot; nel riquadro in alto).</span>
                </p>
                @if ($module->courses->isNotEmpty())
                    <p class="mt-2 text-xs text-base-content/60">
                        Presente in:
                        @foreach ($module->courses as $c)
                            <a href="{{ route('tenant.admin.courses.builder', $c) }}" class="link link-hover font-medium">{{ $c->title }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </p>
                @endif
            </div>

        <section class="card border bg-base-100 builder-module-card">
            <div class="card-body p-5">
                        <div class="space-y-4">
                            @foreach ($module->lessons as $lesson)
                                @php($lt = (string) ($lesson->type?->value ?? $lesson->type))
                                @php($video = $lt === 'video' ? $lesson->videoLesson : null)
                                @php($scorm = $lt === 'scorm' ? $lesson->scormPackage : null)
                                @php($doc = $lt === 'document' ? $lesson->documentLesson : null)
                                @php($summaryVideoStatus = $video ? (string) ($video->status?->value ?? $video->status ?? 'processing') : '')
                                @php($summaryScormStatus = $scorm ? (string) ($scorm->status?->value ?? $scorm->status ?? 'processing') : '')
                                        @php($lessonSec = $lesson->duration_seconds ?? ($lt === 'video' ? $video?->duration_seconds : null))
                                        @php($lessonDurationMmss = \App\Support\DurationFormat::secondsToMmss($lessonSec))
                                        @php($lessonDurMin = $lessonSec === null ? '' : (string) intdiv((int) $lessonSec, 60))
                                        @php($lessonDurSec = $lessonSec === null ? '' : (string) (((int) $lessonSec) % 60))
                                <details class="lesson-collapsible lesson-form-compact lesson-panel-glass builder-lesson-panel card border bg-base-100 p-3" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-2 text-left transition-colors hover:bg-base-200/50 marker:content-none">
                                        <i class="ph ph-caret-right lesson-chevron mt-0.5 shrink-0 text-brand-blue/80 transition-transform duration-200" aria-hidden="true"></i>
                                        @if ($lt === 'video')
                                            @if ($vp = $video?->posterPublicUrl())
                                                <img src="{{ $vp }}" alt="" class="h-10 w-16 shrink-0 rounded-md border border-base-300 object-cover" loading="lazy">
                                            @else
                                                <span class="flex h-10 w-16 shrink-0 items-center justify-center rounded-md border border-base-300 bg-base-200" aria-hidden="true">
                                                    <i class="ph ph-video-camera text-lg text-base-content/50"></i>
                                                </span>
                                            @endif
                                        @endif
                                        <span class="min-w-0 flex-1 truncate font-semibold text-white">{{ $lesson->title }}</span>
                                        <span class="shrink-0 rounded border border-base-300 bg-base-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-base-content/90">{{ $lt }}</span>
                                        @if ($lt === 'video')
                                            <span class="rounded border border-brand-blue/30 bg-brand-blue/10 px-2 py-0.5 text-[11px] font-semibold text-white/90"
                                                  data-content-status
                                                  data-lesson-id="{{ $lesson->id }}">{{ $summaryVideoStatus }}</span>
                                        @elseif ($lt === 'scorm')
                                            <span class="rounded border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-200"
                                                  data-content-status
                                                  data-lesson-id="{{ $lesson->id }}">{{ $summaryScormStatus }}</span>
                                        @endif
                                        @if ($lessonSec !== null)
                                            <span class="shrink-0 rounded border border-brand-amber/45 bg-brand-amber/10 px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-brand-amber"
                                                  title="Durata indicativa">{{ \App\Support\DurationFormat::secondsToMmss($lessonSec) }}</span>
                                        @endif
                                        <span class="lesson-hint-closed shrink-0 text-[11px] font-medium text-white/80">Apri dettagli</span>
                                        <span class="lesson-hint-open shrink-0 text-[11px] font-medium text-base-content/70">Chiudi</span>
                                    </summary>
                                    <div class="mt-3 border-t border-base-300 pt-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                                        <form method="post" action="{{ route('tenant.admin.modules.lessons.update', [$module, $lesson]) }}" class="grid w-full max-w-xl flex-1 gap-2 md:grid-cols-[minmax(0,1fr)_6.5rem_auto_auto]">
                                            @csrf
                                            @method('put')
                                            <input name="title" value="{{ $lesson->title }}" class="input input-bordered input-sm w-full" required minlength="2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="sr-only" for="lesson_duration_minutes_{{ $lesson->id }}">Minuti</label>
                                                <input id="lesson_duration_minutes_{{ $lesson->id }}" name="duration_minutes" value="{{ old('duration_minutes', $lessonDurMin) }}"
                                                       class="input input-bordered input-sm w-full font-mono" placeholder="Min" inputmode="numeric" autocomplete="off"
                                                       min="0" step="1" title="Durata indicativa (opzionale): minuti" aria-label="Minuti (durata)">
                                                <label class="sr-only" for="lesson_duration_seconds_{{ $lesson->id }}">Secondi</label>
                                                <input id="lesson_duration_seconds_{{ $lesson->id }}" name="duration_seconds" value="{{ old('duration_seconds', $lessonDurSec) }}"
                                                       class="input input-bordered input-sm w-full font-mono" placeholder="Sec" inputmode="numeric" autocomplete="off"
                                                       min="0" max="59" step="1" title="Durata indicativa (opzionale): secondi (0–59)" aria-label="Secondi (durata)">
                                            </div>
                                            <label class="flex h-8 items-center gap-2 self-stretch rounded-lg border border-base-300 px-2 text-xs md:h-auto md:self-auto md:py-1.5">
                                                <input type="hidden" name="is_required" value="0">
                                                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $lesson->required ? '1' : '0') === '1') class="checkbox checkbox-primary checkbox-sm shrink-0">
                                                Richiesta
                                            </label>
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                Salva
                                            </button>
                                        </form>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <form method="post" action="{{ route('tenant.admin.modules.lessons.move', [$module, $lesson, 'up']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-up"></i></button>
                                            </form>
                                            <form method="post" action="{{ route('tenant.admin.modules.lessons.move', [$module, $lesson, 'down']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-down"></i></button>
                                            </form>
                                            <form method="post" action="{{ route('tenant.admin.modules.lessons.destroy', [$module, $lesson]) }}" onsubmit="return confirm('Eliminare la lezione?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-ghost btn-sm text-error"><i class="ph ph-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>

                                    @if ($lt === 'video')
                                        @php($vStatus = (string) ($video?->status?->value ?? $video?->status ?? 'processing'))
                                        @php($readyManifestUrl = $vStatus === 'ready' ? ($video?->hlsManifestUrl() ?: null) : null)
                                        @php($hasReadyVideo = $readyManifestUrl !== null)

                                        @if (! $hasReadyVideo)
                                            <div class="mt-5 card bg-base-100 shadow-xl p-3"
                                                    data-video-direct-upload
                                                    data-presign-url="{{ route('api.video.presigned-upload') }}"
                                                    data-finalize-url="{{ route('api.video.finalize-upload') }}"
                                                    data-module-id="{{ $module->id }}"
                                                    data-lesson-id="{{ $lesson->id }}">
                                                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/90">Carica video</div>
                                                <div class="mt-2 flex flex-wrap items-end gap-2">
                                                    <input type="file" data-direct-file accept=".mp4,.m3u8,video/mp4,application/vnd.apple.mpegurl,application/x-mpegURL" class="file-input file-input-bordered file-input-sm w-full min-w-[12rem] flex-1 md:max-w-md">
                                                    <button type="button" data-direct-submit class="btn btn-primary btn-sm">
                                                        Carica video
                                                    </button>
                                                </div>
                                                <p class="mt-2 min-h-[1rem] text-[11px] text-base-content/60" data-direct-status></p>
                                            </div>
                                        @else
                                            <details class="mt-5 card bg-base-100 shadow-xl p-3">
                                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-left marker:content-none">
                                                    <span class="text-xs font-semibold uppercase tracking-wider text-base-content/90">Video pronto</span>
                                                    <span class="text-[11px] font-medium text-base-content/70">Sostituisci video</span>
                                                </summary>
                                                <div class="mt-3 border-t border-base-300 pt-3"
                                                     data-video-direct-upload
                                                     data-presign-url="{{ route('api.video.presigned-upload') }}"
                                                     data-finalize-url="{{ route('api.video.finalize-upload') }}"
                                                     data-module-id="{{ $module->id }}"
                                                     data-lesson-id="{{ $lesson->id }}">
                                                    <div class="mt-2 flex flex-wrap items-end gap-2">
                                                        <input type="file" data-direct-file accept=".mp4,.m3u8,video/mp4,application/vnd.apple.mpegurl,application/x-mpegURL" class="file-input file-input-bordered file-input-sm w-full min-w-[12rem] flex-1 md:max-w-md">
                                                        <button type="button" data-direct-submit class="btn btn-primary btn-sm">
                                                            Carica nuovo video
                                                        </button>
                                                    </div>
                                                    <p class="mt-2 min-h-[1rem] text-[11px] text-base-content/60" data-direct-status></p>
                                                </div>
                                            </details>
                                        @endif

                                        <details class="mt-5 card bg-base-100 shadow-xl p-3">
                                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 marker:content-none">
                                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-base-content/80">
                                                    <i class="ph ph-waveform text-base text-brand-amber/90" aria-hidden="true"></i>
                                                    Stato elaborazione
                                                </span>
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-semibold
                                                        @if ($vStatus === 'ready') border-brand-amber/45 bg-brand-amber/10 text-brand-amber
                                                        @elseif ($vStatus === 'error') border-error/30 bg-error/10 text-error
                                                        @else border-brand-blue/30 bg-brand-blue/10 text-white/90 @endif"
                                                          data-content-status
                                                          data-lesson-id="{{ $lesson->id }}">{{ $vStatus }}</span>
                                                    <span class="text-[11px] font-medium text-base-content/70">Apri</span>
                                                </span>
                                            </summary>

                                            <div class="mt-3 grid gap-3 border-t border-base-300 pt-3">
                                                <p class="text-[11px] text-base-content/60">
                                                    La durata indicativa si imposta nel riquadro <span class="text-base-content/80">Salva</span> sopra. Per le lezioni <span class="font-semibold">SCORM</span> è facoltativa.
                                                    @if (($video?->original_s3 ?? '') !== '' && $vStatus !== 'ready')
                                                        <span class="ml-1 text-base-content/70">Upload ricevuto; conversione in corso.</span>
                                                    @elseif (($video?->original_s3 ?? '') === '' && $vStatus !== 'ready')
                                                        <span class="ml-1 text-base-content/70">In attesa di upload.</span>
                                                    @endif
                                                </p>

                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($vStatus === 'error')
                                                        <form method="post" action="{{ route('tenant.admin.modules.lessons.video.retry', [$module, $lesson]) }}">
                                                            @csrf
                                                            <button class="inline-flex items-center gap-1.5 rounded-lg border border-brand-amber/35 bg-brand-amber/10 px-3 py-2 text-[11px] font-semibold text-brand-amber transition hover:bg-brand-amber/15"
                                                                    title="Riprova a processare il video">
                                                                <i class="ph ph-arrows-clockwise text-[14px]" aria-hidden="true"></i>
                                                                Retry conversione
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <span class="text-[11px] text-base-content/60">
                                                        @if ($vStatus === 'ready')
                                                            Conversione completata; manifest HLS generato dal job.
                                                        @elseif ($vStatus === 'error')
                                                            La conversione non è riuscita. Puoi avviare un retry.
                                                        @else
                                                            In elaborazione: aggiornato automaticamente dal job.
                                                        @endif
                                                    </span>
                                                </div>

                                                @if ($hasReadyVideo)
                                                    <div class="rounded-xl border border-base-300 bg-black/40 p-2">
                                                        <video
                                                            class="video-js vjs-big-play-centered w-full overflow-hidden rounded-lg"
                                                            playsinline
                                                            data-videojs="1"
                                                            @if ($poster = $video?->posterPublicUrl()) poster="{{ $poster }}" @endif
                                                        >
                                                            <source src="{{ $readyManifestUrl }}" type="application/x-mpegURL">
                                                            <track kind="captions" srclang="it" label="Italiano" src="{{ asset('brand/empty-captions.vtt') }}" default>
                                                        </video>
                                                    </div>
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="text-[11px] text-base-content/60">Se non parte, apri lo streaming in una nuova scheda.</span>
                                                        <a href="{{ $readyManifestUrl }}" target="_blank" rel="noreferrer" class="text-[11px] font-semibold text-brand-blue hover:text-brand-amber">
                                                            Apri streaming
                                                        </a>
                                                    </div>
                                                @endif

                                                <form method="post" action="{{ route('tenant.admin.modules.lessons.video.update', [$module, $lesson]) }}">
                                                    @csrf
                                                    @method('put')
                                                    <details class="rounded-lg border border-base-300 bg-base-200/50">
                                                        <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-base-content/70 hover:text-base-content/90">Impostazioni tecniche (solo se necessario)</summary>
                                                        <div class="grid gap-2 border-t border-base-300 p-2.5 md:grid-cols-2">
                                                            <div class="md:col-span-2 text-[11px] text-base-content/60">
                                                                Di norma non servono: il manifest HLS e il file sorgente sono gestiti dal sistema. Usali solo per interventi di supporto.
                                                            </div>
                                                            <div class="md:col-span-2 rounded-lg border border-base-300 bg-base-200 px-3 py-2 text-xs text-base-content/70">
                                                                <span class="font-medium text-base-content/80">Streaming HLS</span>
                                                                <span class="ml-1 text-base-content/60">(chiave oggetto + URL pubblico)</span>
                                                                @if ($video?->hls_manifest)
                                                                    <p class="mt-1 break-all font-mono text-[11px] text-base-content/80">Chiave: {{ $video->hls_manifest }}</p>
                                                                    <p class="mt-1 break-all font-mono text-[11px] text-brand-blue/90">URL: {{ $video->hlsManifestUrl() }}</p>
                                                                @else
                                                                    <p class="mt-1 text-[11px] text-base-content/60">Dopo l’elaborazione comparirà qui la chiave del manifest HLS (.m3u8).</p>
                                                                @endif
                                                            </div>
                                                            <div class="md:col-span-2">
                                                                <label class="label py-1" for="manual_status_{{ $lesson->id }}"><span class="label-text text-xs">Forza stato elaborazione (override)</span></label>
                                                                <select id="manual_status_{{ $lesson->id }}" name="manual_status" class="select select-bordered select-sm w-full font-mono text-xs">
                                                                    <option value="" @selected(old('manual_status', '') === '')>— non modificare —</option>
                                                                    @foreach (['processing', 'ready', 'error'] as $st)
                                                                        <option value="{{ $st }}" @selected(old('manual_status', '') === $st)>{{ $st }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="label py-1" for="original_s3_{{ $lesson->id }}"><span class="label-text text-xs">Percorso sorgente (storage)</span></label>
                                                                <input id="original_s3_{{ $lesson->id }}" name="original_s3" value="{{ old('original_s3', $video?->original_s3) }}" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="opzionale">
                                                            </div>
                                                            <div>
                                                                <label class="label py-1" for="hls_manifest_{{ $lesson->id }}"><span class="label-text text-xs">Chiave manifest HLS (.m3u8) o URL S3</span></label>
                                                                <input id="hls_manifest_{{ $lesson->id }}" name="hls_manifest" value="{{ old('hls_manifest', $video?->hls_manifest) }}" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="es. tenants/.../master.m3u8">
                                                            </div>
                                                            <div class="md:col-span-2 pt-1">
                                                                <button class="btn btn-primary btn-sm">
                                                                    Salva impostazioni tecniche
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </details>
                                                </form>
                                            </div>
                                        </details>
                                    @endif

                                    @if ($lt === 'scorm')
                                        @php($sStatus = (string) ($scorm?->status?->value ?? $scorm?->status ?? 'processing'))
                                        @php($launchUrl = $scorm?->launchUrl() ?: '')

                                        <details class="mt-5 card bg-base-100 shadow-xl p-3">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-left marker:content-none">
                                                <span class="text-xs font-semibold uppercase tracking-wider text-base-content/90">Pacchetto SCORM</span>
                                                <span class="text-[11px] font-medium text-base-content/70">{{ $scorm ? 'Sostituisci SCORM' : 'Carica SCORM' }}</span>
                                            </summary>
                                            <div class="mt-3 border-t border-base-300 pt-3">
                                                <p class="text-[11px] text-base-content/60">
                                                    Carica un file <span class="font-mono text-base-content/80">.zip</span>. Il sistema lo estrae e prepara la lezione SCORM per i partecipanti.
                                                </p>

                                                <form method="post"
                                                      enctype="multipart/form-data"
                                                      action="{{ route('tenant.admin.modules.lessons.scorm.upload', [$module, $lesson]) }}"
                                                      class="mt-3 grid gap-2 md:grid-cols-[1fr_180px_auto]">
                                                    @csrf
                                                    <input type="file" name="scorm_file" accept=".zip,application/zip,application/x-zip-compressed" class="input input-bordered input-sm w-full" required>
                                                    <select name="version" class="select select-bordered select-sm w-full font-mono text-xs">
                                                        @foreach (['1.2','2004'] as $version)
                                                            <option value="{{ $version }}" @selected(($scorm?->version?->value ?? $scorm?->version ?? '1.2')===$version)>{{ $version }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-primary btn-sm">
                                                        Carica SCORM
                                                    </button>
                                                </form>

                                                @if ($scorm && (string) ($scorm->s3_path ?? '') !== '')
                                                    <div class="mt-3 rounded-lg border border-base-300 bg-base-300 px-3 py-2 text-[11px] text-base-content/70">
                                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-base-content/60">File sorgente</div>
                                                        <div class="mt-1 break-all font-mono text-base-content/80">{{ $scorm->s3_path }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>

                                        <details class="mt-5 card bg-base-100 shadow-xl p-3">
                                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 marker:content-none">
                                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-base-content/80">
                                                    <i class="ph ph-puzzle-piece text-base text-brand-amber/90" aria-hidden="true"></i>
                                                    Stato elaborazione
                                                </span>
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-semibold
                                                        @if ($sStatus === 'ready') border-brand-amber/45 bg-brand-amber/10 text-brand-amber
                                                        @elseif ($sStatus === 'error') border-error/30 bg-error/10 text-error
                                                        @else border-amber-500/30 bg-amber-500/10 text-amber-200 @endif"
                                                          data-content-status
                                                          data-lesson-id="{{ $lesson->id }}">{{ $sStatus }}</span>
                                                    <span class="text-[11px] font-medium text-base-content/70">Apri</span>
                                                </span>
                                            </summary>

                                            <div class="mt-3 grid gap-3 border-t border-base-300 pt-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($sStatus === 'error')
                                                        <form method="post" action="{{ route('tenant.admin.modules.lessons.scorm.retry', [$module, $lesson]) }}">
                                                            @csrf
                                                            <button class="inline-flex items-center gap-1.5 rounded-lg border border-brand-amber/35 bg-brand-amber/10 px-3 py-2 text-[11px] font-semibold text-brand-amber transition hover:bg-brand-amber/15"
                                                                    title="Riprova a processare il pacchetto SCORM">
                                                                <i class="ph ph-arrows-clockwise text-[14px]" aria-hidden="true"></i>
                                                                Retry estrazione
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($sStatus === 'ready' && $launchUrl !== '')
                                                        <a href="{{ $launchUrl }}" target="_blank" rel="noreferrer"
                                                           class="inline-flex items-center gap-1.5 rounded-lg border border-base-300 bg-base-200 px-3 py-2 text-[11px] font-semibold text-base-content/90 transition hover:bg-base-300">
                                                            <i class="ph ph-arrow-square-out text-[14px]" aria-hidden="true"></i>
                                                            Apri launch URL
                                                        </a>
                                                    @endif
                                                    <span class="text-[11px] text-base-content/60">
                                                        @if ($sStatus === 'ready')
                                                            Elaborazione completata; pacchetto pronto.
                                                        @elseif ($sStatus === 'error')
                                                            Elaborazione non riuscita. Puoi avviare un retry.
                                                        @else
                                                            In elaborazione: aggiornato automaticamente dal job.
                                                        @endif
                                                    </span>
                                                </div>

                                                <form method="post" action="{{ route('tenant.admin.modules.lessons.scorm.update', [$module, $lesson]) }}">
                                                    @csrf
                                                    @method('put')
                                                    <details class="rounded-lg border border-base-300 bg-base-200/50">
                                                        <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-base-content/70 hover:text-base-content/90">Impostazioni tecniche (solo se necessario)</summary>
                                                        <div class="grid gap-2 border-t border-base-300 p-2.5 md:grid-cols-2">
                                                            <div class="md:col-span-2 text-[11px] text-base-content/60">
                                                                Di norma non servono: usa solo per interventi di supporto.
                                                            </div>
                                                            <div class="md:col-span-2">
                                                                <label class="label py-1" for="scorm_s3_path_{{ $lesson->id }}"><span class="label-text text-xs">Launch path (chiave oggetto o URL)</span></label>
                                                                <input id="scorm_s3_path_{{ $lesson->id }}" name="s3_path" value="{{ old('s3_path', $scorm?->s3_path) }}" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="es. tenants/.../index.html">
                                                            </div>
                                                            <div>
                                                                <label class="label py-1" for="scorm_version_{{ $lesson->id }}"><span class="label-text text-xs">Versione</span></label>
                                                                <select id="scorm_version_{{ $lesson->id }}" name="version" class="select select-bordered select-sm w-full font-mono text-xs">
                                                                    @foreach (['1.2','2004'] as $version)
                                                                        <option value="{{ $version }}" @selected(old('version', ($scorm?->version?->value ?? $scorm?->version ?? '1.2'))===$version)>{{ $version }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="label py-1" for="scorm_status_{{ $lesson->id }}"><span class="label-text text-xs">Forza stato (override)</span></label>
                                                                <select id="scorm_status_{{ $lesson->id }}" name="status" class="select select-bordered select-sm w-full font-mono text-xs">
                                                                    @foreach (['processing','ready','error'] as $status)
                                                                        <option value="{{ $status }}" @selected(old('status', ($scorm?->status?->value ?? $scorm?->status ?? 'processing'))===$status)>{{ $status }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="md:col-span-2 pt-1">
                                                                <button class="btn btn-primary btn-sm">
                                                                    Salva impostazioni SCORM
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </details>
                                                </form>
                                            </div>
                                        </details>
                                    @endif

                                    @if ($lt === 'document')
                                        <div class="mt-3 rounded-xl border border-violet-500/20 bg-violet-500/5 p-2.5">
                                            <div class="text-xs font-semibold uppercase tracking-wider text-violet-300">Documento (PDF)</div>
                                            <p class="mt-1 text-[11px] text-base-content/60">Carica un PDF leggibile dai partecipanti del corso (anteprima nella pagina lezione).</p>
                                            @if ($doc?->file_path)
                                                <p class="mt-2 text-xs text-base-content/70">
                                                    <span class="font-medium text-base-content/60">File attuale:</span>
                                                    <span class="mt-0.5 block break-all font-mono text-[11px] font-semibold leading-snug text-white sm:mt-0 sm:inline sm:ml-1">{{ $doc->original_filename ?: basename($doc->file_path) }}</span>
                                                </p>
                                            @endif
                                            <form method="post"
                                                  enctype="multipart/form-data"
                                                  action="{{ route('tenant.admin.modules.lessons.document.upload', [$module, $lesson]) }}"
                                                  class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end">
                                                @csrf
                                                <input type="file" name="document_file" accept=".pdf,application/pdf" class="file-input file-input-bordered file-input-sm w-full min-w-0 flex-1" required>
                                                <button type="submit" class="btn btn-secondary btn-sm shrink-0">
                                                    Carica PDF
                                                </button>
                                            </form>
                                            @error('document_file')
                                                <p class="mt-2 text-xs text-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>

                        <form method="post" action="{{ route('tenant.admin.modules.lessons.store', [$module]) }}" class="lesson-form-compact mt-4 grid w-full min-w-0 grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_8.5rem_7.5rem_auto] lg:items-center">
                            @csrf
                            <input name="title" class="input input-bordered input-sm w-full min-w-0 sm:col-span-2 lg:col-span-1" placeholder="Titolo nuova lezione (min. 2 caratteri)..." required minlength="2" value="{{ old('title') }}">
                            <div class="min-w-0">
                                <label class="sr-only" for="new_lesson_type">Tipo lezione</label>
                                <select id="new_lesson_type" name="type" class="select select-bordered select-sm w-full min-w-0 font-mono text-xs">
                                @foreach ($lessonTypes as $t)
                                    <option value="{{ $t->value }}" @selected(old('type', 'document') === $t->value)>{{ $t->value }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="sr-only" for="new_lesson_duration_minutes">Minuti</label>
                                <input id="new_lesson_duration_minutes" name="duration_minutes" value="{{ old('duration_minutes') }}"
                                       class="input input-bordered input-sm w-full min-w-0 font-mono" placeholder="Min" inputmode="numeric" autocomplete="off"
                                       min="0" step="1" aria-label="Minuti (durata opzionale)">
                                <label class="sr-only" for="new_lesson_duration_seconds">Secondi</label>
                                <input id="new_lesson_duration_seconds" name="duration_seconds" value="{{ old('duration_seconds') }}"
                                       class="input input-bordered input-sm w-full min-w-0 font-mono" placeholder="Sec" inputmode="numeric" autocomplete="off"
                                       min="0" max="59" step="1" aria-label="Secondi (durata opzionale, 0–59)">
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2 sm:col-span-2 lg:col-span-1">
                                <label class="flex h-10 items-center gap-1.5 rounded-lg border border-base-300 px-3 text-xs">
                                    <input type="hidden" name="is_required" value="0">
                                    <input type="checkbox" name="is_required" value="1" @checked(old('is_required', '1') === '1') class="checkbox checkbox-primary checkbox-sm shrink-0">
                                    Richiesta
                                </label>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ph ph-plus-circle mr-1.5 align-[-2px]" aria-hidden="true"></i>
                                    Aggiungi lezione
                                </button>
                            </div>
                        </form>
            </div>
        </section>
        </x-ui.page>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-content-status-url]');
            if (!root) return;
            const endpoint = root.getAttribute('data-content-status-url');
            if (!endpoint) return;

            const paint = (el, status, type) => {
                el.textContent = status;
                if (type === 'video') {
                    el.className = 'inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-semibold';
                    el.classList.add(
                        'border',
                        status === 'ready' ? 'border-brand-amber/45' : status === 'error' ? 'border-error/30' : 'border-brand-blue/30',
                        status === 'ready' ? 'bg-brand-amber/10' : status === 'error' ? 'bg-error/10' : 'bg-brand-blue/10',
                        status === 'ready' ? 'text-brand-amber' : status === 'error' ? 'text-error' : 'text-white/90'
                    );
                    return;
                }

                el.className = 'rounded px-2 py-1 text-[11px] font-semibold';
                el.classList.add(
                    'border',
                    status === 'ready' ? 'border-brand-amber/45' : status === 'error' ? 'border-error/30' : 'border-amber-500/30',
                    status === 'ready' ? 'bg-brand-amber/10' : status === 'error' ? 'bg-error/10' : 'bg-amber-500/10',
                    status === 'ready' ? 'text-brand-amber' : status === 'error' ? 'text-error' : 'text-amber-200'
                );
            };

            const refresh = async () => {
                try {
                    const res = await fetch(endpoint, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Skip-Loader': '1',
                        },
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    const items = payload?.items || {};

                    document.querySelectorAll('[data-content-status][data-lesson-id]').forEach((el) => {
                        const lessonId = el.getAttribute('data-lesson-id');
                        const item = items[lessonId];
                        if (!item) return;
                        paint(el, item.status || 'processing', item.type || 'video');
                    });
                } catch (_) {
                    // silent
                }
            };

            refresh();
            setInterval(refresh, 8000);
        })();
    </script>

    @push('scripts')
        @vite(['resources/js/video-player.js'])
    @endpush
</x-layouts.tenant>

