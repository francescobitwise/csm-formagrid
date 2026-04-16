<x-layouts.tenant :title="'Catalogo corsi'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-white">Catalogo corsi</h1>
            <p class="mt-1 text-sm text-slate-400">Tutti i corsi pubblicati per la tua organizzazione.</p>
        </div>

        <form method="get" action="{{ route('tenant.courses.index') }}" class="glass-card mb-8 flex flex-col gap-3 rounded-xl border border-white/5 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-500" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $q }}" placeholder="Cerca per titolo o descrizione…"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/80 py-2.5 pl-10 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-brand-blue/50 focus:outline-none focus:ring-1 focus:ring-brand-blue/40">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy">Cerca</button>
                @if ($q !== '')
                    <a href="{{ route('tenant.courses.index') }}" class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5">Azzera</a>
                @endif
            </div>
        </form>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($courses as $course)
                <a href="{{ route('tenant.courses.show', $course) }}"
                   class="glass-card group flex flex-col overflow-hidden rounded-2xl border border-white/5 transition hover:border-brand-blue/25 hover:bg-white/[0.03]">
                    <div class="relative aspect-[16/10] w-full overflow-hidden bg-gradient-to-br from-slate-800/80 to-slate-950/90">
                        @if ($url = $course->thumbnailPublicUrl())
                            <img src="{{ $url }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-slate-600">
                                <i class="ph ph-image text-5xl opacity-40" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs uppercase tracking-wider text-slate-500">Corso</span>
                            @if ($course->user_enrolled)
                                <span class="rounded-full border border-brand-amber/45 bg-brand-amber/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-amber">Iscritto</span>
                            @endif
                        </div>
                        <h2 class="mt-2 text-lg font-semibold text-white group-hover:text-brand-amber">{{ $course->title }}</h2>
                        <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-400">{{ $course->description }}</p>
                        <div class="mt-4 flex items-center gap-4 text-xs text-slate-500">
                            <span>{{ $course->modules_count }} moduli</span>
                            <span>{{ $course->lessons_count }} lezioni</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="glass-card rounded-2xl border border-white/5 p-8 text-sm text-slate-400">
                    Nessun corso pubblicato.
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $courses->links() }}</div>
    </div>
</x-layouts.tenant>

