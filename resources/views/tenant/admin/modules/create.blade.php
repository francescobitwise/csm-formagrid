<x-layouts.tenant :title="'Nuovo modulo — Admin'">
    <div class="mx-auto max-w-[720px] px-6 py-10">
        <x-ui.page>
            <x-ui.flash />

            <x-ui.page-header title="Nuovo modulo" subtitle="Il modulo sarà nella libreria; poi potrai associarlo ai corsi dal builder.">
                <x-slot:actions>
                    <a href="{{ route('tenant.admin.modules.index') }}" class="btn btn-outline shrink-0">Torna ai moduli</a>
                </x-slot:actions>
            </x-ui.page-header>

            <form method="post" action="{{ route('tenant.admin.modules.store') }}" class="space-y-5">
                @csrf
                <div class="form-control w-full">
                    <label class="label" for="title">
                        <span class="label-text">Titolo</span>
                    </label>
                    <input id="title" name="title" value="{{ old('title') }}" class="input input-bordered w-full" required minlength="2" maxlength="200" placeholder="Es. Introduzione al corso">
                    @error('title')
                        <p class="mt-2 text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary">Crea modulo</button>
                </div>
            </form>
        </x-ui.page>
    </div>
</x-layouts.tenant>
