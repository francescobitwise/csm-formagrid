@php
    $lt = (string) ($lesson->type?->value ?? $lesson->type);
    $video = $lt === 'video' ? $lesson->videoLesson : null;
    $scorm = $lt === 'scorm' ? $lesson->scormPackage : null;
    $summaryStatus = match ($lt) {
        'video' => $video ? (string) ($video->status?->value ?? $video->status ?? 'processing') : null,
        'scorm' => $scorm ? (string) ($scorm->status?->value ?? $scorm->status ?? 'processing') : null,
        default => null,
    };
    $typeLabel = match ($lt) {
        'video' => 'Video',
        'scorm' => 'SCORM',
        'document' => 'Documento',
        default => ucfirst($lt),
    };
    $lessonSec = $lesson->duration_seconds ?? ($lt === 'video' ? $video?->duration_seconds : null);
    $position = $loop->iteration;
@endphp

<div
    class="lesson-list-item"
    data-lesson-row="{{ $lesson->id }}"
>
    <div class="lesson-list-summary flex items-center gap-3 px-4 py-2.5 lg:px-6">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-center gap-3 text-left"
            data-lesson-open="{{ $lesson->id }}"
            aria-haspopup="dialog"
            aria-controls="lesson-drawer"
        >
            <span class="w-6 shrink-0 text-center font-mono text-xs tabular-nums text-base-content/55">{{ $position }}</span>
            <span class="min-w-0 flex-1 truncate text-sm font-medium text-base-content">{{ $lesson->title }}</span>
            <span class="hidden w-24 shrink-0 text-xs text-base-content/60 sm:block">{{ $typeLabel }}</span>
            <span class="hidden w-28 shrink-0 justify-start sm:flex">
                @if ($summaryStatus)
                    @include('tenant.admin.modules.partials.status-pill', ['status' => $summaryStatus, 'lessonId' => $lesson->id])
                @else
                    <span class="text-xs text-base-content/50">—</span>
                @endif
            </span>
            <span class="hidden w-14 shrink-0 text-right font-mono text-xs tabular-nums text-base-content/60 sm:block" title="Durata indicativa">
                @if ($lessonSec !== null)
                    {{ \App\Support\DurationFormat::secondsToMmss($lessonSec) }}
                @else
                    —
                @endif
            </span>
        </button>

        <span class="flex shrink-0 items-center gap-0.5 text-base-content/70">
            <button
                type="button"
                class="btn btn-ghost btn-xs btn-square hover:text-base-content sm:hidden"
                data-lesson-open="{{ $lesson->id }}"
                title="Modifica"
                aria-label="Modifica lezione"
            >
                <i class="ph ph-pencil-simple text-sm"></i>
            </button>
            <form method="post" action="{{ route('tenant.admin.modules.lessons.move', [$module, $lesson, 'up']) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs btn-square hover:text-base-content" title="Sposta su" aria-label="Sposta su"><i class="ph ph-arrow-up text-sm"></i></button>
            </form>
            <form method="post" action="{{ route('tenant.admin.modules.lessons.move', [$module, $lesson, 'down']) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs btn-square hover:text-base-content" title="Sposta giù" aria-label="Sposta giù"><i class="ph ph-arrow-down text-sm"></i></button>
            </form>
            <form method="post" action="{{ route('tenant.admin.modules.lessons.destroy', [$module, $lesson]) }}" onsubmit="return confirm('Eliminare la lezione?')">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-ghost btn-xs btn-square text-error/80 hover:text-error" title="Elimina" aria-label="Elimina"><i class="ph ph-trash text-sm"></i></button>
            </form>
        </span>
    </div>
</div>
