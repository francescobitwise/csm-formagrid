@php
    $status = (string) ($status ?? 'processing');
    $pillClass = match ($status) {
        'ready' => 'bg-success/15 text-success',
        'error' => 'bg-error/15 text-error',
        default => 'bg-warning/15 text-warning',
    };
@endphp
<span
    @class([
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize',
        $pillClass,
    ])
    @if (! empty($lessonId))
        data-content-status
        data-lesson-id="{{ $lessonId }}"
    @endif
>{{ $status }}</span>
