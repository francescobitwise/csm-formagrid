@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
    $cardClass = 'relative overflow-hidden border border-base-300 bg-base-100';
    if ($href) {
        $cardClass .= ' transition hover:border-primary/40 hover:bg-base-200/40';
    }
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $cardClass]) }}>
    @if ($icon)
        <div class="pointer-events-none absolute right-0 top-0 p-4 text-base-content/15">
            <i class="ph {{ $icon }} text-6xl"></i>
        </div>
    @endif
    <div class="p-5">
        <div class="stat p-0">
            <div class="stat-title text-base-content/60">{{ $title }}</div>
            <div class="stat-value text-3xl text-base-content">{{ $value }}</div>
            @if ($description)
                <div class="stat-desc text-base-content/65">{{ $description }}</div>
            @endif
        </div>
    </div>
</{{ $tag }}>
