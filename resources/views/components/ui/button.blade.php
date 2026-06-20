@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
])

@php
    $classes = match ($variant) {
        'primary' => 'btn btn-primary',
        'secondary' => 'btn btn-outline',
        'ghost' => 'btn btn-ghost',
        'danger' => 'btn btn-error btn-outline',
        'accent' => 'btn btn-accent',
        default => 'btn btn-primary',
    };
    $classes .= match ($size) {
        'sm' => ' btn-sm',
        'lg' => ' btn-lg',
        default => '',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="ph {{ $icon }}"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="ph {{ $icon }}"></i>@endif
        {{ $slot }}
    </button>
@endif
