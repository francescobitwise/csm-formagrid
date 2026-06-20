<x-layouts.tenant :title="'I miei corsi'">
    <div class="mx-auto max-w-[1440px] px-4 py-10 lg:px-6">
        <x-ui.page-header title="I miei corsi" subtitle="Corsi a cui sei iscritto e avanzamento." />

        <div class="grid gap-5 md:grid-cols-3">
            <x-ui.stat
                title="Corsi attivi"
                :value="$count"
                description="Iscrizioni confermate"
            />
            <x-ui.stat
                title="Progresso medio"
                :value="$avgProgress.'%'"
                description="Media sui tuoi corsi"
            />
            <x-ui.stat
                title="Certificati"
                :value="$certificateCount ?? 0"
                description="Corsi completati con attestato PDF"
            />
        </div>

        <div class="mt-10">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <h2 class="text-lg font-semibold">Continua da qui</h2>
                <x-ui.button href="{{ route('tenant.courses.index') }}" variant="secondary" size="sm" icon="ph-books">
                    Esplora il catalogo
                </x-ui.button>
            </div>

            @forelse ($enrollments as $enrollment)
                @php($c = $enrollment->course)
                @continue($c === null)
                @php($pct = (float) ($enrollment->progress_pct ?? 0))
                @php($pctClamped = (int) min(100, max(0, $pct)))
                <a href="{{ route('tenant.courses.show', $c) }}"
                   class="card bordered bg-base-100 mb-3 transition hover:shadow-md">
                    <div class="card-body flex flex-col gap-4 p-5 sm:flex-row sm:items-stretch">
                        <div class="relative h-28 w-full shrink-0 overflow-hidden rounded-xl bg-base-300 sm:h-24 sm:w-40">
                            @if ($url = $c->thumbnailPublicUrl())
                                <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                        <i class="ph ph-play-circle text-3xl" aria-hidden="true"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs uppercase tracking-wider text-base-content/60">Corso</div>
                            <h3 class="mt-1 text-lg font-semibold">{{ $c->title }}</h3>
                            @if ($c->description)
                                <p class="mt-1 line-clamp-2 text-sm text-base-content/70">{{ $c->description }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-base-content/60">
                                <span>Iscritto il {{ $enrollment->enrolled_at?->format('d/m/Y') }}</span>
                                @if ($enrollment->status?->value === 'completed')
                                    <span class="badge badge-success badge-sm">Completato</span>
                                    <a href="{{ route('tenant.courses.certificate', $c) }}"
                                       class="link link-primary inline-flex items-center gap-1 text-xs font-semibold">
                                        <i class="ph ph-download-simple" aria-hidden="true"></i> Certificato
                                    </a>
                                @endif
                            </div>
                            <progress class="progress progress-primary mt-3 w-full max-w-md"
                                      value="{{ $pctClamped }}"
                                      max="100"
                                      aria-label="Avanzamento: {{ $pctClamped }} percento"></progress>
                            @if ($count === 1 && $pctClamped === 0)
                                <div class="alert alert-warning mt-3 py-2 text-xs">
                                    <i class="ph ph-lightning" aria-hidden="true"></i>
                                    <span>Inizia ora: apri il corso e completa la prima lezione.</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end justify-center gap-1 text-base-content/60 sm:pl-2">
                            <span class="text-sm font-medium text-primary">Apri</span>
                            <i class="ph ph-caret-right text-xl" aria-hidden="true"></i>
                        </div>
                    </div>
                </a>
            @empty
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body items-center p-10 text-center">
                        <p class="text-base-content/70">Non sei ancora iscritto a nessun corso.</p>
                        <x-ui.button href="{{ route('tenant.courses.index') }}" class="mt-6" icon="ph-magnifying-glass">
                            Sfoglia il catalogo
                        </x-ui.button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.tenant>
