<form method="post" action="{{ route('tenant.admin.learners.store') }}" class="space-y-5" id="create-learner-form">
    @csrf
    <input type="hidden" name="form_intent" value="create_learner">

    <p class="text-sm text-base-content/65">Ruolo learner. Puoi lasciare vuota la password per generarne una casuale.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="form-control w-full">
            <label class="label" for="create_learner_first_name">
                <span class="label-text">Nome</span>
            </label>
            <input id="create_learner_first_name" name="first_name" value="{{ old('first_name') }}" type="text" class="input input-bordered w-full" required>
            @error('first_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-control w-full">
            <label class="label" for="create_learner_last_name">
                <span class="label-text">Cognome</span>
            </label>
            <input id="create_learner_last_name" name="last_name" value="{{ old('last_name') }}" type="text" class="input input-bordered w-full" required>
            @error('last_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_learner_email">
            <span class="label-text">Email</span>
        </label>
        <input id="create_learner_email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" required autocomplete="off">
        @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="form-control w-full">
            <label class="label" for="create_learner_tax_code">
                <span class="label-text">Codice fiscale</span>
            </label>
            <input id="create_learner_tax_code" name="tax_code" value="{{ old('tax_code') }}" type="text" class="input input-bordered w-full" required>
            @error('tax_code') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-control w-full">
            <label class="label" for="create_learner_phone">
                <span class="label-text">Numero di telefono</span>
            </label>
            <input id="create_learner_phone" name="phone" value="{{ old('phone') }}" type="text" class="input input-bordered w-full" required placeholder="+39 …">
            @error('phone') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_learner_company_id">
            <span class="label-text">Azienda (opzionale)</span>
        </label>
        <select id="create_learner_company_id" name="company_id" class="select select-bordered w-full">
            <option value="">— Nessuna —</option>
            @foreach(($companies ?? []) as $company)
                <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        @error('company_id') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_learner_password">
            <span class="label-text">Password (opzionale)</span>
        </label>
        <input id="create_learner_password" name="password" type="password" class="input input-bordered w-full" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
        @error('password') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex cursor-pointer items-start gap-3 text-sm">
        <input type="hidden" name="send_credentials_email" value="0">
        <input type="checkbox" name="send_credentials_email" value="1" class="checkbox checkbox-primary mt-0.5 shrink-0" @checked(old('send_credentials_email', true))>
        <span>
            <span class="font-medium">Invia subito email con credenziali</span>
            <span class="mt-0.5 block text-xs text-base-content/60">URL di accesso e password (consigliato).</span>
        </span>
    </label>

    <label class="flex cursor-pointer items-start gap-3 text-sm">
        <input type="hidden" name="night_hours_override" value="0">
        <input type="checkbox" name="night_hours_override" value="1" class="checkbox checkbox-primary mt-0.5 shrink-0" @checked(old('night_hours_override'))>
        <span>
            <span class="font-medium">Override orari notturni</span>
            <span class="mt-0.5 block text-xs text-base-content/60">Può accedere alla fascia notturna dei corsi che la prevedono.</span>
        </span>
    </label>
</form>
