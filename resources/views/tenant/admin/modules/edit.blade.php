<x-layouts.tenant :title="'Modifica modulo — Admin'">
    <x-ui.page>
        <x-ui.page-header title="Modifica modulo" :subtitle="$module->title">
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.modules.index') }}" class="link link-hover">Moduli</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">Modifica</span>
            </x-slot:breadcrumb>
            <x-slot:actions>
                <a href="{{ route('tenant.admin.modules.index') }}" class="btn btn-outline shrink-0">Torna ai moduli</a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="border-b border-base-300 px-4 py-6 lg:px-6">
            <form method="post" action="{{ route('tenant.admin.modules.update', $module) }}" class="mx-auto max-w-xl space-y-5">
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

            <form method="post" action="{{ route('tenant.admin.modules.destroy', $module) }}" class="mx-auto mt-10 max-w-xl border-t border-base-300 pt-8"
                  onsubmit="return confirm('Eliminare definitivamente questo modulo e tutte le sue lezioni?')">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-error btn-outline">
                    Elimina modulo
                </button>
            </form>
        </div>
    </x-ui.page>
</x-layouts.tenant>
