<form method="post" action="{{ route('tenant.admin.modules.store') }}" class="space-y-5" id="create-module-form">
    @csrf
    <input type="hidden" name="form_intent" value="create_module">

    <p class="text-sm text-base-content/65">Il modulo sarà nella libreria; poi potrai associarlo ai corsi dal builder.</p>

    <div class="form-control w-full">
        <label class="label" for="create_module_title">
            <span class="label-text">Titolo</span>
        </label>
        <input id="create_module_title" name="title" value="{{ old('title') }}" class="input input-bordered w-full" required minlength="2" maxlength="200" placeholder="Es. Introduzione al corso">
        @error('title')
            <p class="mt-2 text-sm text-error">{{ $message }}</p>
        @enderror
    </div>
</form>
