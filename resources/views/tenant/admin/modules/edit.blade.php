<x-layouts.tenant :title="'Modifica modulo — Admin'">
    <div class="mx-auto max-w-[720px] px-6 py-10">
        <div class="admin-page-wrap">
            @if (session('toast'))
                <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    {{ session('toast') }}
                </div>
            @endif

            <div class="admin-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Modifica modulo</h1>
                    <p class="admin-subtitle">{{ $module->title }}</p>
                </div>
                <a href="{{ route('tenant.admin.modules.index') }}" class="admin-btn-secondary shrink-0">Torna ai moduli</a>
            </div>

            <form method="post" action="{{ route('tenant.admin.modules.update', $module) }}" class="space-y-5">
                @csrf
                @method('put')
                <div>
                    <label class="form-label" for="title">Titolo</label>
                    <input id="title" name="title" value="{{ old('title', $module->title) }}" class="form-input" required minlength="2" maxlength="200">
                    @error('title')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="admin-btn-primary">Salva</button>
                    <a href="{{ route('tenant.admin.modules.lessons', $module) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-blue/35 bg-brand-blue/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-blue/15">
                        <i class="ph ph-list-numbers"></i>
                        Gestisci lezioni
                    </a>
                </div>
            </form>

            <form method="post" action="{{ route('tenant.admin.modules.destroy', $module) }}" class="mt-10 border-t border-white/10 pt-8"
                  onsubmit="return confirm('Eliminare definitivamente questo modulo e tutte le sue lezioni?')">
                @csrf
                @method('delete')
                <button type="submit" class="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-500/20">
                    Elimina modulo
                </button>
            </form>
        </div>
    </div>
</x-layouts.tenant>
