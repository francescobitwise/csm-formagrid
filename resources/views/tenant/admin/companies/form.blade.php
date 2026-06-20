<x-layouts.tenant :title="($company ? 'Modifica azienda' : 'Nuova azienda').' — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <x-ui.page>
            <a href="{{ route('tenant.admin.companies.index') }}" class="link link-hover text-sm">&larr; Aziende</a>

            <div class="mt-4">
                <x-ui.page-header
                    :title="$company ? 'Modifica azienda' : 'Nuova azienda'"
                    subtitle="Crea e gestisci le aziende per assegnazioni corsi e report. Ragione sociale e P.IVA sono obbligatorie."
                />
            </div>

            <div class="card bg-base-100 shadow-xl mt-6">
                <div class="card-body">
                    <form method="post"
                          action="{{ $company ? route('tenant.admin.companies.update', $company) : route('tenant.admin.companies.store') }}"
                          class="space-y-5">
                        @csrf
                        @if ($company)
                            @method('put')
                        @endif

                        <div class="card bg-base-100 border border-base-300">
                            <div class="card-body p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Dati fiscali</div>
                                <div class="mt-4 space-y-4">
                                    <div class="form-control w-full">
                                        <label class="label" for="legal_name">
                                            <span class="label-text">Ragione sociale</span>
                                        </label>
                                        <input id="legal_name" name="legal_name" value="{{ old('legal_name', $company?->legal_name) }}" type="text" class="input input-bordered w-full" required>
                                        @error('legal_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="form-control w-full">
                                        <label class="label" for="vat">
                                            <span class="label-text">P.IVA / VAT</span>
                                        </label>
                                        <input id="vat" name="vat" value="{{ old('vat', $company?->vat) }}" type="text" class="input input-bordered w-full" placeholder="Es. IT01234567890" required>
                                        @error('vat') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-base-100 border border-base-300">
                            <div class="card-body p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Contatti</div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div class="form-control w-full sm:col-span-2">
                                        <label class="label" for="email">
                                            <span class="label-text">Email (opzionale)</span>
                                        </label>
                                        <input id="email" name="email" value="{{ old('email', $company?->email) }}" type="email" class="input input-bordered w-full" placeholder="amministrazione@azienda.it">
                                        @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="label" for="phone">
                                            <span class="label-text">Telefono (opzionale)</span>
                                        </label>
                                        <input id="phone" name="phone" value="{{ old('phone', $company?->phone) }}" type="text" class="input input-bordered w-full" placeholder="+39 …">
                                        @error('phone') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="label" for="contact_name">
                                            <span class="label-text">Referente (opzionale)</span>
                                        </label>
                                        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}" type="text" class="input input-bordered w-full" placeholder="Nome e cognome">
                                        @error('contact_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-base-100 border border-base-300">
                            <div class="card-body p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Indirizzo</div>
                                <div class="mt-4 space-y-4">
                                    <div class="form-control w-full">
                                        <label class="label" for="address_line1">
                                            <span class="label-text">Indirizzo (opzionale)</span>
                                        </label>
                                        <input id="address_line1" name="address_line1" value="{{ old('address_line1', $company?->address_line1) }}" type="text" class="input input-bordered w-full" placeholder="Via/Piazza, numero civico">
                                        @error('address_line1') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="label" for="address_line2">
                                            <span class="label-text">Indirizzo (riga 2, opzionale)</span>
                                        </label>
                                        <input id="address_line2" name="address_line2" value="{{ old('address_line2', $company?->address_line2) }}" type="text" class="input input-bordered w-full" placeholder="Scala, interno, c/o…">
                                        @error('address_line2') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="form-control w-full">
                                            <label class="label" for="postal_code">
                                                <span class="label-text">CAP (opzionale)</span>
                                            </label>
                                            <input id="postal_code" name="postal_code" value="{{ old('postal_code', $company?->postal_code) }}" type="text" class="input input-bordered w-full" placeholder="00100">
                                            @error('postal_code') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="form-control w-full">
                                            <label class="label" for="city">
                                                <span class="label-text">Città (opzionale)</span>
                                            </label>
                                            <input id="city" name="city" value="{{ old('city', $company?->city) }}" type="text" class="input input-bordered w-full" placeholder="Roma">
                                            @error('city') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="form-control w-full">
                                            <label class="label" for="province">
                                                <span class="label-text">Provincia (opzionale)</span>
                                            </label>
                                            <input id="province" name="province" value="{{ old('province', $company?->province) }}" type="text" class="input input-bordered w-full" placeholder="RM">
                                            @error('province') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="form-control w-full">
                                            <label class="label" for="country">
                                                <span class="label-text">Nazione (opzionale)</span>
                                            </label>
                                            <input id="country" name="country" value="{{ old('country', $company?->country) }}" type="text" class="input input-bordered w-full" placeholder="IT">
                                            <p class="mt-2 text-xs text-base-content/60">Formato ISO 2 lettere (es. <span class="font-mono">IT</span>).</p>
                                            @error('country') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="notes">
                                <span class="label-text">Note (opzionali)</span>
                            </label>
                            <textarea id="notes" name="notes" rows="4" class="textarea textarea-bordered w-full" placeholder="Annotazioni interne…">{{ old('notes', $company?->notes) }}</textarea>
                            @error('notes') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="btn btn-primary">{{ $company ? 'Salva' : 'Crea azienda' }}</button>
                            <a href="{{ route('tenant.admin.companies.index') }}" class="btn btn-outline">Annulla</a>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.page>
    </div>
</x-layouts.tenant>
