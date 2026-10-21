@php
    $lt = (string) ($lesson->type?->value ?? $lesson->type);
    $video = $lt === 'video' ? $lesson->videoLesson : null;
    $scorm = $lt === 'scorm' ? $lesson->scormPackage : null;
    $doc = $lt === 'document' ? $lesson->documentLesson : null;
    $typeLabel = match ($lt) {
        'video' => 'Video',
        'scorm' => 'SCORM',
        'document' => 'Documento',
        default => ucfirst($lt),
    };
    $lessonSec = $lesson->duration_seconds ?? ($lt === 'video' ? $video?->duration_seconds : null);
    $lessonDurMin = $lessonSec === null ? '' : (string) intdiv((int) $lessonSec, 60);
    $lessonDurSec = $lessonSec === null ? '' : (string) (((int) $lessonSec) % 60);
@endphp

<div
    class="lesson-drawer-panel hidden h-full flex-col"
    data-lesson-panel="{{ $lesson->id }}"
    hidden
>
    <div class="flex items-start justify-between gap-3 border-b border-base-300 px-5 py-4">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-base-content/45">{{ $typeLabel }}</p>
            <h2 class="mt-0.5 truncate text-lg font-semibold text-base-content">{{ $lesson->title }}</h2>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-square" data-lesson-close aria-label="Chiudi">
            <i class="ph ph-x text-lg"></i>
        </button>
    </div>

    <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
        <form method="post" action="{{ route('tenant.admin.modules.lessons.update', [$module, $lesson]) }}" class="space-y-3">
            @csrf
            @method('put')
            <div>
                <label class="mb-1 block text-[11px] font-medium text-base-content/50" for="lesson_title_{{ $lesson->id }}">Titolo</label>
                <input id="lesson_title_{{ $lesson->id }}" name="title" value="{{ $lesson->title }}" class="input input-bordered input-sm w-full bg-base-100" required minlength="2">
            </div>
            @if ($lt === 'video')
                <div class="rounded-lg border border-base-300/70 bg-base-200/40 px-3 py-2">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/45">Durata</p>
                    @if ($lessonSec !== null && (int) $lessonSec > 0)
                        <p class="mt-0.5 font-mono text-sm tabular-nums text-base-content">{{ \App\Support\DurationFormat::secondsToMmss((int) $lessonSec) }}</p>
                        <p class="mt-0.5 text-[11px] text-base-content/55">Rilevata automaticamente dal video.</p>
                    @else
                        <p class="mt-0.5 text-sm text-base-content/70">Verrà rilevata dopo il caricamento / conversione.</p>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-base-content/50" for="lesson_duration_minutes_{{ $lesson->id }}">Min</label>
                        <input id="lesson_duration_minutes_{{ $lesson->id }}" name="duration_minutes" value="{{ old('duration_minutes', $lessonDurMin) }}"
                               class="input input-bordered input-sm w-full bg-base-100 font-mono" placeholder="0" inputmode="numeric" autocomplete="off"
                               min="0" step="1" title="Durata indicativa (opzionale): minuti" aria-label="Minuti (durata)">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-base-content/50" for="lesson_duration_seconds_{{ $lesson->id }}">Sec</label>
                        <input id="lesson_duration_seconds_{{ $lesson->id }}" name="duration_seconds" value="{{ old('duration_seconds', $lessonDurSec) }}"
                               class="input input-bordered input-sm w-full bg-base-100 font-mono" placeholder="0" inputmode="numeric" autocomplete="off"
                               min="0" max="59" step="1" title="Durata indicativa (opzionale): secondi (0–59)" aria-label="Secondi (durata)">
                    </div>
                </div>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-xs text-base-content/70">
                    <input type="hidden" name="is_required" value="0">
                    <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $lesson->required ? '1' : '0') === '1') class="checkbox checkbox-primary checkbox-sm shrink-0">
                    Richiesta
                </label>
                <button type="submit" class="btn btn-primary btn-sm">Salva</button>
            </div>
        </form>

        @if ($lt === 'video')
            @include('tenant.admin.modules.partials.lesson-media-video', compact('module', 'lesson', 'video'))
        @elseif ($lt === 'scorm')
            @include('tenant.admin.modules.partials.lesson-media-scorm', compact('module', 'lesson', 'scorm'))
        @elseif ($lt === 'document')
            @include('tenant.admin.modules.partials.lesson-media-document', compact('module', 'lesson', 'doc'))
        @endif
    </div>
</div>
