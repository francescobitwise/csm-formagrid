@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
        @isset($subtitle)
            <div class="mt-1 text-sm text-base-content/70">{{ $subtitle }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
