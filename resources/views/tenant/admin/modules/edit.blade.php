<x-layouts.tenant :title="'Modifica modulo — Admin'">
    <div class="mx-auto max-w-[720px] px-6 py-10">
        <x-ui.page>
            <x-ui.flash />

            <x-ui.page-header title="Modifica modulo" :subtitle="$module->title">
                <x-slot:actions>
                    <a href="{{ route('tenant.admin.modules.index') }}" class="btn btn-outline shrink-0">Torna ai moduli</a>
                </x-slot:actions>
            </x-ui.page-header>

            <form method="post" action="{{ route('tenant.admin.modules.update', $module) }}" class="space-y-5">
                @csrf
                @method('put')
                <div class="form-control w-full">
                    <label class="label" for="title">
                        <span class="label-text">Titolo</span>
                    </label>
                    <input id="title" name="title" value="{{ old('title', $module->title) }}" class="input input-bordered w-full" required minlength="2" maxlength="200">
                    @error('title')
                        <p class="mt-2 text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <a href="{{ route('tenant.admin.modules.lessons', $module) }}" class="btn btn-outline inline-flex items-center gap-2">
                        <i class="ph ph-list-numbers"></i>
                        Gestisci lezioni
                    </a>
                </div>
            </form>

            <form method="post" action="{{ route('tenant.admin.modules.destroy', $module) }}" class="mt-10 border-t border-base-300 pt-8"
                  onsubmit="return confirm('Eliminare definitivamente questo modulo e tutte le sue lezioni?')">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-error btn-outline">
                    Elimina modulo
                </button>
            </form>
        </x-ui.page>
    </div>
</x-layouts.tenant>
