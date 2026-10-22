<x-layouts.tenant :title="$lesson->title.' — Anteprima'">
    <header class="border-b border-base-300 bg-base-100 px-4 py-5 lg:px-6">
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-base-content/50">
            <a href="{{ route('tenant.admin.modules.index') }}" class="link link-hover">Moduli</a>
            <i class="ph ph-caret-right text-[10px]" aria-hidden="true"></i>
            <a href="{{ route('tenant.admin.modules.lessons', $module) }}" class="link link-hover">{{ $module->title }}</a>
            <i class="ph ph-caret-right text-[10px]" aria-hidden="true"></i>
            <span class="text-base-content/70">Anteprima</span>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-base-content sm:text-3xl">{{ $lesson->title }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-base-content/60">
                    Il video viene letto dal sito. Il bucket resta chiuso e questa visione non conta come progresso allievo.
                </p>
            </div>
            <a href="{{ route('tenant.admin.modules.lessons', $module) }}" class="btn btn-outline btn-sm shrink-0">
                Torna alle lezioni
            </a>
        </div>
    </header>

    <div class="mx-auto max-w-5xl px-4 py-6 lg:px-6">
        <div class="overflow-hidden rounded-2xl bg-black ring-1 ring-base-300/40">
            <video
                class="video-js vjs-big-play-centered w-full"
                playsinline
                controls
                data-videojs="1"
                @if ($poster = $video->posterPublicUrl()) poster="{{ $poster }}" @endif
            >
                <source src="{{ $manifestUrl }}" type="application/x-mpegURL">
                <track kind="captions" srclang="it" label="Italiano" src="{{ asset('brand/empty-captions.vtt') }}" default>
            </video>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/video-player.js'])
    @endpush
</x-layouts.tenant>
