@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold leading-snug tracking-tight">{{ $title }}</h1>
        @isset($subtitle)
            <div class="mt-1 text-sm text-base-content/70">{{ $subtitle }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex max-w-full flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
