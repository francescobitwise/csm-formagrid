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
                @include('tenant.learner.courses.partials.course-card', [
                    'course' => $course,
                    'showEnrolledBadge' => true,
                ])
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
