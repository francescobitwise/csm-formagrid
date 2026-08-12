@props(['title', 'subtitle' => null])

<header {{ $attributes->merge(['class' => 'border-b border-base-300 bg-base-100 px-4 py-5 lg:px-6']) }}>
    @isset($breadcrumb)
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-base-content/60">
            {{ $breadcrumb }}
        </div>
    @endisset

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-base-content sm:text-3xl">{{ $title }}</h1>
            @isset($subtitle)
                <div class="mt-1 max-w-2xl text-sm text-base-content/65">{{ $subtitle }}</div>
            @endisset
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
