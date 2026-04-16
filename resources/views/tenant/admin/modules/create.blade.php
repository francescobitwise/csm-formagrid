<x-layouts.tenant :title="'Nuovo modulo — Admin'">
    <div class="mx-auto max-w-[720px] px-6 py-10">
        <div class="admin-page-wrap">
            @if (session('toast'))
                <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    {{ session('toast') }}
                </div>
            @endif

            <div class="admin-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Nuovo modulo</h1>
                    <p class="admin-subtitle">Il modulo sarà nella libreria; poi potrai associarlo ai corsi dal builder.</p>
                </div>
                <a href="{{ route('tenant.admin.modules.index') }}" class="admin-btn-secondary shrink-0">Torna ai moduli</a>
            </div>

            <form method="post" action="{{ route('tenant.admin.modules.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="form-label" for="title">Titolo</label>
                    <input id="title" name="title" value="{{ old('title') }}" class="form-input" required minlength="2" maxlength="200" placeholder="Es. Introduzione al corso">
                    @error('title')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="admin-btn-primary">Crea modulo</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.tenant>
