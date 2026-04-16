<x-layouts.tenant :title="'I miei corsi'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <h1 class="text-3xl font-bold tracking-tight text-white">I miei corsi</h1>
        <p class="mt-2 text-slate-400">Corsi a cui sei iscritto e avanzamento.</p>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <div class="glass-card rounded-xl border border-white/5 p-6">
                <div class="text-sm font-medium text-slate-400">Corsi attivi</div>
                <div class="mt-2 text-2xl font-semibold text-white">{{ $count }}</div>
                <div class="mt-1 text-xs text-slate-500">Iscrizioni confermate</div>
            </div>
            <div class="glass-card rounded-xl border border-white/5 p-6">
                <div class="text-sm font-medium text-slate-400">Progresso medio</div>
                <div class="mt-2 text-2xl font-semibold text-white">{{ $avgProgress }}%</div>
                <div class="mt-1 text-xs text-slate-500">Media sui tuoi corsi</div>
            </div>
            <div class="glass-card rounded-xl border border-white/5 p-6">
                <div class="text-sm font-medium text-slate-400">Certificati</div>
                <div class="mt-2 text-2xl font-semibold text-white">{{ $certificateCount ?? 0 }}</div>
                <div class="mt-1 text-xs text-slate-500">Corsi completati con attestato PDF</div>
            </div>
        </div>

        <div class="mt-10">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <h2 class="text-lg font-semibold text-white">Continua da qui</h2>
                <a href="{{ route('tenant.courses.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-brand-blue/35 bg-brand-blue/10 px-4 py-2 text-sm font-semibold text-white/90 transition hover:bg-brand-blue/15">
                    <i class="ph ph-books"></i> Esplora il catalogo
                </a>
            </div>

            @forelse ($enrollments as $enrollment)
                @php($c = $enrollment->course)
                @continue($c === null)
                <a href="{{ route('tenant.courses.show', $c) }}"
                   class="glass-card mb-3 flex flex-col gap-4 rounded-2xl border border-white/5 p-5 transition hover:border-brand-blue/25 sm:flex-row sm:items-stretch">
                    <div class="relative h-28 w-full shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-slate-800/80 to-slate-950/90 ring-1 ring-white/5 sm:h-24 sm:w-40">
                        @if ($url = $c->thumbnailPublicUrl())
                            <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-brand-blue/25 bg-brand-blue/10 text-brand-blue/90">
                                    <i class="ph ph-play-circle text-3xl" aria-hidden="true"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs uppercase tracking-wider text-slate-500">Corso</div>
                        <h3 class="mt-1 text-lg font-semibold text-white">{{ $c->title }}</h3>
                        @if ($c->description)
                            <p class="mt-1 line-clamp-2 text-sm text-slate-400">{{ $c->description }}</p>
                        @endif
                        @php($pct = (float) ($enrollment->progress_pct ?? 0))
                        @php($pctClamped = (int) min(100, max(0, $pct)))
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span>Iscritto il {{ $enrollment->enrolled_at?->format('d/m/Y') }}</span>
                            @if ($enrollment->status?->value === 'completed')
                                <span class="text-brand-amber/90">Completato</span>
                                <a href="{{ route('tenant.courses.certificate', $c) }}"
                                   class="ml-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-blue hover:text-brand-amber">
                                    <i class="ph ph-download-simple" aria-hidden="true"></i> Certificato
                                </a>
                            @endif
                        </div>
                        <div class="mt-3 h-2 w-full max-w-md overflow-hidden rounded-full bg-slate-200 dark:bg-white/10"
                             role="progressbar"
                             aria-valuenow="{{ $pctClamped }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-label="Avanzamento: {{ $pctClamped }} percento">
                            <div class="h-full rounded-full bg-brand-blue/80" @style(['width: '.$pctClamped.'%'])></div>
                        </div>
                        @if ($count === 1 && $pctClamped === 0)
                            <div class="mt-3 inline-flex items-center gap-2 rounded-xl border border-brand-amber/30 bg-brand-amber/10 px-3 py-2 text-xs font-semibold text-brand-amber">
                                <i class="ph ph-lightning" aria-hidden="true"></i>
                                Inizia ora: apri il corso e completa la prima lezione.
                            </div>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-col items-end justify-center gap-1 text-slate-500 dark:text-slate-400 sm:pl-2">
                        <span class="text-sm font-medium text-brand-blue">Apri</span>
                        <i class="ph ph-caret-right text-xl" aria-hidden="true"></i>
                    </div>
                </a>
            @empty
                <div class="glass-card rounded-2xl border border-white/5 p-10 text-center">
                    <p class="text-slate-400">Non sei ancora iscritto a nessun corso.</p>
                    <a href="{{ route('tenant.courses.index') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-blue px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        <i class="ph ph-magnifying-glass"></i> Sfoglia il catalogo
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.tenant>
