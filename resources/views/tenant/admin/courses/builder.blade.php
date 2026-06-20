<x-layouts.tenant :title="'Moduli del corso — '.$course->title">
    <div class="mx-auto max-w-[1320px] px-6 py-10">
        <x-ui.page>
            <x-ui.flash />

            <x-ui.page-header :title="$course->title" subtitle="Associa moduli dalla libreria e definisci ordine e obbligatorietà. Le lezioni si gestiscono nella pagina di ogni modulo.">
                <x-slot:actions>
                    <a href="{{ route('tenant.admin.courses.edit', $course) }}" class="btn btn-outline">
                        Dettagli corso
                    </a>
                    <a href="{{ route('tenant.admin.courses.index') }}" class="btn btn-outline">
                        Torna ai corsi
                    </a>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="card bg-base-100 shadow-xl mb-8">
                <div class="card-body">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-base-content/60">Associa modulo</h2>
                        <p class="mt-1 text-xs text-base-content/60">Crea i moduli in <a href="{{ route('tenant.admin.modules.index') }}" class="link link-hover">Moduli</a>, aggiungi le lezioni con «Gestisci lezioni», poi torna qui.</p>
                    </div>
                    @if ($availableModules->isEmpty())
                        <p class="text-sm text-base-content/70">Tutti i moduli sono già associati o non ne hai ancora creati. <a href="{{ route('tenant.admin.modules.create') }}" class="link link-hover font-medium">Crea un modulo</a>.</p>
                    @else
                        <form method="post" action="{{ route('tenant.admin.courses.modules.store', $course) }}" class="grid gap-4 md:grid-cols-[1fr_auto_auto]">
                            @csrf
                            @error('module_id')
                                <p class="md:col-span-3 text-sm text-error">{{ $message }}</p>
                            @enderror
                            <select name="module_id" class="select select-bordered w-full" required>
                                <option value="" disabled @selected(! old('module_id'))>Seleziona un modulo…</option>
                                @foreach ($availableModules as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('module_id') === $opt->id)>{{ $opt->title }}</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 rounded-lg border border-base-300 px-4 py-3 text-sm">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', '1') === '1') class="checkbox checkbox-primary">
                                Richiesto nel corso
                            </label>
                            <button type="submit" class="btn btn-primary">
                                Associa modulo
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($course->modules as $module)
                    <section class="card border bg-base-100 builder-module-card">
                        <div class="flex flex-wrap items-center gap-2 border-b border-base-300 px-5 py-4">
                            <span class="badge badge-neutral badge-sm">
                                Modulo {{ $module->pivot->position }}
                            </span>
                            <div class="text-sm font-semibold">{{ $module->title }}</div>
                            <span class="text-xs text-base-content/60">({{ $module->lessons_count }} {{ $module->lessons_count === 1 ? 'lezione' : 'lezioni' }})</span>

                            <div class="ml-auto flex items-center gap-2">
                                <form method="post" action="{{ route('tenant.admin.courses.modules.move', [$course, $module, 'up']) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Su"><i class="ph ph-arrow-up"></i></button>
                                </form>
                                <form method="post" action="{{ route('tenant.admin.courses.modules.move', [$course, $module, 'down']) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Giù"><i class="ph ph-arrow-down"></i></button>
                                </form>
                                <form method="post" action="{{ route('tenant.admin.courses.modules.destroy', [$course, $module]) }}" onsubmit="return confirm('Rimuovere questo modulo dal corso? Il modulo resta in libreria.')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-ghost btn-sm text-error" title="Rimuovi dal corso"><i class="ph ph-trash"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="card-body p-5">
                            <form method="post" action="{{ route('tenant.admin.courses.modules.update', [$course, $module]) }}" class="mb-4 grid gap-3 md:grid-cols-[1fr_auto_auto]">
                                @csrf
                                @method('put')
                                <input name="title" value="{{ $module->title }}" class="input input-bordered w-full" required minlength="2">
                                <label class="flex items-center gap-2 rounded-lg border border-base-300 px-4 py-3 text-sm">
                                    <input type="hidden" name="is_required" value="0">
                                    <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $module->pivot->required ? '1' : '0') === '1') class="checkbox checkbox-primary">
                                    Richiesto nel corso
                                </label>
                                <button type="submit" class="btn btn-outline">
                                    Salva
                                </button>
                            </form>

                            <a href="{{ route('tenant.admin.modules.lessons', $module) }}" class="btn btn-primary inline-flex items-center gap-2">
                                <i class="ph ph-list-numbers"></i>
                                Gestisci lezioni
                            </a>
                        </div>
                    </section>
                @empty
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body text-sm text-base-content/70">
                            Nessun modulo associato: crea un modulo in <a href="{{ route('tenant.admin.modules.create') }}" class="link link-hover">Moduli</a>, aggiungi le lezioni, poi torna qui per associarlo.
                        </div>
                    </div>
                @endforelse
            </div>
        </x-ui.page>
    </div>
</x-layouts.tenant>
