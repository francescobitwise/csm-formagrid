<x-layouts.tenant :title="'Moduli del corso — '.$course->title">
    <div class="mx-auto max-w-[1320px] px-6 py-10">
        <div class="admin-page-wrap">
        @if (session('toast'))
            <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                {{ session('toast') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                <p class="font-semibold text-rose-50">Controlla i campi e riprova.</p>
                <ul class="mt-2 list-inside list-disc text-rose-200/90">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="admin-title">{{ $course->title }}</h1>
                <p class="admin-subtitle">Associa moduli dalla libreria e definisci ordine e obbligatorietà. Le lezioni si gestiscono nella pagina di ogni modulo.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('tenant.admin.courses.edit', $course) }}"
                   class="admin-btn-secondary">
                    Dettagli corso
                </a>
                <a href="{{ route('tenant.admin.courses.index') }}"
                   class="admin-btn-secondary">
                    Torna ai corsi
                </a>
            </div>
        </div>

        <div class="mb-8 glass-panel rounded-2xl p-6">
            <div class="mb-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Associa modulo</h2>
                <p class="mt-1 text-xs text-slate-500">Crea i moduli in <a href="{{ route('tenant.admin.modules.index') }}" class="text-slate-300 underline decoration-white/20 underline-offset-2 hover:text-white">Moduli</a>, aggiungi le lezioni con «Gestisci lezioni», poi torna qui.</p>
            </div>
            @if ($availableModules->isEmpty())
                <p class="text-sm text-slate-400">Tutti i moduli sono già associati o non ne hai ancora creati. <a href="{{ route('tenant.admin.modules.create') }}" class="font-medium text-slate-200 underline decoration-white/20 underline-offset-2 hover:text-white">Crea un modulo</a>.</p>
            @else
                <form method="post" action="{{ route('tenant.admin.courses.modules.store', $course) }}" class="grid gap-4 md:grid-cols-[1fr_auto_auto]">
                    @csrf
                    @error('module_id')
                        <p class="md:col-span-3 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                    <select name="module_id" class="form-input" required>
                        <option value="" disabled @selected(! old('module_id'))>Seleziona un modulo…</option>
                        @foreach ($availableModules as $opt)
                            <option value="{{ $opt->id }}" @selected(old('module_id') === $opt->id)>{{ $opt->title }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-600/60 bg-slate-950/20 px-4 py-3 text-sm text-slate-300">
                        <input type="hidden" name="is_required" value="0">
                        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', '1') === '1') class="h-4 w-4 rounded border-slate-600 bg-slate-950/50 text-brand-blue focus:ring-brand-blue/40">
                        Richiesto nel corso
                    </label>
                    <button type="submit" class="rounded-xl border border-brand-blue/35 bg-brand-blue/10 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-blue/15">
                        Associa modulo
                    </button>
                </form>
            @endif
        </div>

        <div class="space-y-5">
            @forelse ($course->modules as $module)
                <section class="glass-card builder-module-card rounded-2xl border border-white/8">
                    <div class="flex flex-wrap items-center gap-2 border-b border-white/8 px-5 py-4">
                        <span class="rounded-md bg-slate-800/90 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Modulo {{ $module->pivot->position }}
                        </span>
                        <div class="text-sm font-semibold text-white">{{ $module->title }}</div>
                        <span class="text-xs text-slate-500">({{ $module->lessons_count }} {{ $module->lessons_count === 1 ? 'lezione' : 'lezioni' }})</span>

                        <div class="ml-auto flex items-center gap-2">
                            <form method="post" action="{{ route('tenant.admin.courses.modules.move', [$course, $module, 'up']) }}">
                                @csrf
                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-white" title="Su"><i class="ph ph-arrow-up"></i></button>
                            </form>
                            <form method="post" action="{{ route('tenant.admin.courses.modules.move', [$course, $module, 'down']) }}">
                                @csrf
                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-white" title="Giù"><i class="ph ph-arrow-down"></i></button>
                            </form>
                            <form method="post" action="{{ route('tenant.admin.courses.modules.destroy', [$course, $module]) }}" onsubmit="return confirm('Rimuovere questo modulo dal corso? Il modulo resta in libreria.')">
                                @csrf
                                @method('delete')
                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-rose-400" title="Rimuovi dal corso"><i class="ph ph-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <div class="p-5">
                        <form method="post" action="{{ route('tenant.admin.courses.modules.update', [$course, $module]) }}" class="mb-4 grid gap-3 md:grid-cols-[1fr_auto_auto]">
                            @csrf
                            @method('put')
                            <input name="title" value="{{ $module->title }}" class="form-input" required minlength="2">
                            <label class="flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-3 text-sm text-slate-300">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $module->pivot->required ? '1' : '0') === '1') class="h-4 w-4 rounded border-slate-600 bg-slate-950/50 text-brand-blue focus:ring-brand-blue/40">
                                Richiesto nel corso
                            </label>
                            <button type="submit" class="rounded-xl border border-slate-600/80 bg-slate-800/90 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-700">
                                Salva
                            </button>
                        </form>

                        <a href="{{ route('tenant.admin.modules.lessons', $module) }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-brand-blue/35 bg-brand-blue/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-blue/15">
                            <i class="ph ph-list-numbers"></i>
                            Gestisci lezioni
                        </a>
                    </div>
                </section>
            @empty
                <div class="glass-card rounded-2xl border border-white/5 p-8 text-sm text-slate-400">
                    Nessun modulo associato: crea un modulo in <a href="{{ route('tenant.admin.modules.create') }}" class="text-slate-200 underline decoration-white/20 underline-offset-2 hover:text-white">Moduli</a>, aggiungi le lezioni, poi torna qui per associarlo.
                </div>
            @endforelse
        </div>
        </div>
    </div>
</x-layouts.tenant>
