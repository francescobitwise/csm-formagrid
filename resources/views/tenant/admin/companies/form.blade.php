<x-layouts.tenant :title="($company ? 'Modifica azienda' : 'Nuova azienda').' — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.companies.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Aziende</a>
            <h1 class="admin-title mt-4">{{ $company ? 'Modifica azienda' : 'Nuova azienda' }}</h1>
            <p class="admin-subtitle mt-1">Crea e gestisci le aziende per assegnazioni corsi e report. Tutti i campi, tranne il nome, sono opzionali.</p>

            <form method="post"
                  action="{{ $company ? route('tenant.admin.companies.update', $company) : route('tenant.admin.companies.store') }}"
                  class="glass-card mt-6 space-y-5 rounded-xl border border-white/5 p-6">
                @csrf
                @if ($company)
                    @method('put')
                @endif

                <div>
                    <label class="form-label" for="name">Nome</label>
                    <input id="name" name="name" value="{{ old('name', $company?->name) }}" type="text" class="form-input" required>
                    @error('name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dati fiscali</div>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="form-label" for="legal_name">Ragione sociale (opzionale)</label>
                            <input id="legal_name" name="legal_name" value="{{ old('legal_name', $company?->legal_name) }}" type="text" class="form-input" placeholder="Se diversa dal nome">
                            @error('legal_name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label" for="vat">P.IVA / VAT (opzionale)</label>
                            <input id="vat" name="vat" value="{{ old('vat', $company?->vat) }}" type="text" class="form-input" placeholder="Es. IT01234567890">
                            @error('vat') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Contatti</div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="form-label" for="email">Email (opzionale)</label>
                            <input id="email" name="email" value="{{ old('email', $company?->email) }}" type="email" class="form-input" placeholder="amministrazione@azienda.it">
                            @error('email') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="phone">Telefono (opzionale)</label>
                            <input id="phone" name="phone" value="{{ old('phone', $company?->phone) }}" type="text" class="form-input" placeholder="+39 …">
                            @error('phone') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="contact_name">Referente (opzionale)</label>
                            <input id="contact_name" name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}" type="text" class="form-input" placeholder="Nome e cognome">
                            @error('contact_name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Indirizzo</div>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="form-label" for="address_line1">Indirizzo (opzionale)</label>
                            <input id="address_line1" name="address_line1" value="{{ old('address_line1', $company?->address_line1) }}" type="text" class="form-input" placeholder="Via/Piazza, numero civico">
                            @error('address_line1') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="address_line2">Indirizzo (riga 2, opzionale)</label>
                            <input id="address_line2" name="address_line2" value="{{ old('address_line2', $company?->address_line2) }}" type="text" class="form-input" placeholder="Scala, interno, c/o…">
                            @error('address_line2') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label" for="postal_code">CAP (opzionale)</label>
                                <input id="postal_code" name="postal_code" value="{{ old('postal_code', $company?->postal_code) }}" type="text" class="form-input" placeholder="00100">
                                @error('postal_code') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="city">Città (opzionale)</label>
                                <input id="city" name="city" value="{{ old('city', $company?->city) }}" type="text" class="form-input" placeholder="Roma">
                                @error('city') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label" for="province">Provincia (opzionale)</label>
                                <input id="province" name="province" value="{{ old('province', $company?->province) }}" type="text" class="form-input" placeholder="RM">
                                @error('province') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="country">Nazione (opzionale)</label>
                                <input id="country" name="country" value="{{ old('country', $company?->country) }}" type="text" class="form-input" placeholder="IT">
                                <p class="mt-2 text-xs text-slate-500">Formato ISO 2 lettere (es. <span class="font-mono">IT</span>).</p>
                                @error('country') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="notes">Note (opzionali)</label>
                    <textarea id="notes" name="notes" rows="4" class="form-input" placeholder="Annotazioni interne…">{{ old('notes', $company?->notes) }}</textarea>
                    @error('notes') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">{{ $company ? 'Salva' : 'Crea azienda' }}</button>
                    <a href="{{ route('tenant.admin.companies.index') }}" class="admin-btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.tenant>

