<x-layouts.tenant :title="'Catalogo corsi'">
    <div class="mx-auto max-w-[1440px] px-4 py-10 lg:px-6">
        <x-ui.page-header title="Catalogo corsi" subtitle="Tutti i corsi pubblicati per la tua organizzazione." />

        <div class="card bordered bg-base-100 mb-8">
            <div class="card-body p-4">
                <form method="get" action="{{ route('tenant.courses.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="input input-bordered flex flex-1 items-center gap-2">
                        <i class="ph ph-magnifying-glass text-lg text-base-content/50" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $q }}" placeholder="Cerca per titolo o descrizione…" class="grow bg-transparent">
                    </label>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Cerca</button>
                        @if ($q !== '')
                            <a href="{{ route('tenant.courses.index') }}" class="btn btn-outline btn-sm">Azzera</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($courses as $course)
                <a href="{{ route('tenant.courses.show', $course) }}"
                   class="card bordered bg-base-100 group overflow-hidden transition hover:shadow-md">
                    <figure class="relative aspect-[16/10] w-full overflow-hidden bg-base-300">
                        @if ($url = $course->thumbnailPublicUrl())
                            <img src="{{ $url }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-base-content/30">
                                <i class="ph ph-image text-5xl opacity-40" aria-hidden="true"></i>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body flex flex-1 flex-col p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs uppercase tracking-wider text-base-content/60">Corso</span>
                            @if ($course->user_enrolled)
                                <span class="badge badge-warning badge-sm">Iscritto</span>
                            @endif
                        </div>
                        <h2 class="card-title mt-2 text-lg group-hover:text-primary">{{ $course->title }}</h2>
                        <p class="line-clamp-2 flex-1 text-sm text-base-content/70">{{ $course->description }}</p>
                        <div class="mt-4 flex items-center gap-4 text-xs text-base-content/60">
                            <span>{{ $course->modules_count }} moduli</span>
                            <span>{{ $course->lessons_count }} lezioni</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="card bg-base-100 shadow-xl sm:col-span-2 lg:col-span-3">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Nessun corso pubblicato.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $courses->links() }}</div>
    </div>
</x-layouts.tenant>
