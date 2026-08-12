<form method="post" action="{{ route('tenant.admin.companies.store') }}" class="space-y-5" id="create-company-form">
    @csrf
    <input type="hidden" name="form_intent" value="create_company">

    <p class="text-sm text-base-content/65">Ragione sociale e P.IVA sono obbligatorie.</p>

    <div class="border border-base-300 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Dati fiscali</div>
        <div class="mt-4 space-y-4">
            <div class="form-control w-full">
                <label class="label" for="create_company_legal_name">
                    <span class="label-text">Ragione sociale</span>
                </label>
                <input id="create_company_legal_name" name="legal_name" value="{{ old('legal_name') }}" type="text" class="input input-bordered w-full" required>
                @error('legal_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-control w-full">
                <label class="label" for="create_company_vat">
                    <span class="label-text">P.IVA / VAT</span>
                </label>
                <input id="create_company_vat" name="vat" value="{{ old('vat') }}" type="text" class="input input-bordered w-full" placeholder="Es. IT01234567890" required>
                @error('vat') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="border border-base-300 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Contatti</div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="form-control w-full sm:col-span-2">
                <label class="label" for="create_company_email">
                    <span class="label-text">Email (opzionale)</span>
                </label>
                <input id="create_company_email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" placeholder="amministrazione@azienda.it">
                @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-control w-full">
                <label class="label" for="create_company_phone">
                    <span class="label-text">Telefono (opzionale)</span>
                </label>
                <input id="create_company_phone" name="phone" value="{{ old('phone') }}" type="text" class="input input-bordered w-full" placeholder="+39 …">
                @error('phone') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-control w-full">
                <label class="label" for="create_company_contact_name">
                    <span class="label-text">Referente (opzionale)</span>
                </label>
                <input id="create_company_contact_name" name="contact_name" value="{{ old('contact_name') }}" type="text" class="input input-bordered w-full" placeholder="Nome e cognome">
                @error('contact_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="border border-base-300 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Indirizzo</div>
        <div class="mt-4 space-y-4">
            <div class="form-control w-full">
                <label class="label" for="create_company_address_line1">
                    <span class="label-text">Indirizzo (opzionale)</span>
                </label>
                <input id="create_company_address_line1" name="address_line1" value="{{ old('address_line1') }}" type="text" class="input input-bordered w-full" placeholder="Via/Piazza, numero civico">
                @error('address_line1') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-control w-full">
                <label class="label" for="create_company_address_line2">
                    <span class="label-text">Indirizzo (riga 2, opzionale)</span>
                </label>
                <input id="create_company_address_line2" name="address_line2" value="{{ old('address_line2') }}" type="text" class="input input-bordered w-full" placeholder="Scala, interno, c/o…">
                @error('address_line2') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="form-control w-full">
                    <label class="label" for="create_company_postal_code">
                        <span class="label-text">CAP (opzionale)</span>
                    </label>
                    <input id="create_company_postal_code" name="postal_code" value="{{ old('postal_code') }}" type="text" class="input input-bordered w-full" placeholder="00100">
                    @error('postal_code') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-control w-full">
                    <label class="label" for="create_company_city">
                        <span class="label-text">Città (opzionale)</span>
                    </label>
                    <input id="create_company_city" name="city" value="{{ old('city') }}" type="text" class="input input-bordered w-full" placeholder="Roma">
                    @error('city') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="form-control w-full">
                    <label class="label" for="create_company_province">
                        <span class="label-text">Provincia (opzionale)</span>
                    </label>
                    <input id="create_company_province" name="province" value="{{ old('province') }}" type="text" class="input input-bordered w-full" placeholder="RM">
                    @error('province') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-control w-full">
                    <label class="label" for="create_company_country">
                        <span class="label-text">Nazione (opzionale)</span>
                    </label>
                    <input id="create_company_country" name="country" value="{{ old('country') }}" type="text" class="input input-bordered w-full" placeholder="IT">
                    <p class="mt-2 text-xs text-base-content/60">Formato ISO 2 lettere (es. <span class="font-mono">IT</span>).</p>
                    @error('country') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_company_notes">
            <span class="label-text">Note (opzionali)</span>
        </label>
        <textarea id="create_company_notes" name="notes" rows="3" class="textarea textarea-bordered w-full" placeholder="Annotazioni interne…">{{ old('notes') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>
</form>
