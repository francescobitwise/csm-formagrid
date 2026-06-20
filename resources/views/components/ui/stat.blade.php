@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
    $cardClass = 'card bg-base-100 shadow-lg relative overflow-hidden';
    if ($href) {
        $cardClass .= ' transition hover:shadow-xl';
    }
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $cardClass]) }}>
    @if ($icon)
        <div class="pointer-events-none absolute right-0 top-0 p-4 opacity-10">
            <i class="ph {{ $icon }} text-6xl"></i>
        </div>
    @endif
    <div class="card-body p-5">
        <div class="stat p-0">
            <div class="stat-title">{{ $title }}</div>
            <div class="stat-value text-3xl">{{ $value }}</div>
            @if ($description)
                <div class="stat-desc">{{ $description }}</div>
            @endif
        </div>
    </div>
</{{ $tag }}>
