@props(['type' => 'info', 'dismiss' => null])

@php
    $alertClass = match ($type) {
        'success' => 'alert alert-success',
        'warning' => 'alert alert-warning',
        'error' => 'alert alert-error',
        'info' => 'alert alert-info',
        default => 'alert',
    };
@endphp

<div {{ $attributes->merge(['class' => $alertClass]) }} @if($dismiss) data-auto-dismiss="{{ $dismiss }}" @endif>
    {{ $slot }}
</div>
