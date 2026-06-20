<x-layouts.tenant :title="'Nuovo allievo — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <x-ui.page>
            <a href="{{ route('tenant.admin.learners.index') }}" class="link link-hover text-sm">&larr; Allievi</a>

            <div class="mt-4">
                <x-ui.page-header title="Nuovo allievo" subtitle="Ruolo learner. Puoi lasciare vuota la password per generarne una casuale." />
            </div>

            <div class="card bg-base-100 shadow-xl mt-6">
                <div class="card-body">
                    <form method="post" action="{{ route('tenant.admin.learners.store') }}" class="space-y-5">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-control w-full">
                                <label class="label" for="first_name">
                                    <span class="label-text">Nome</span>
                                </label>
                                <input id="first_name" name="first_name" value="{{ old('first_name') }}" type="text" class="input input-bordered w-full" required>
                                @error('first_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-control w-full">
                                <label class="label" for="last_name">
                                    <span class="label-text">Cognome</span>
                                </label>
                                <input id="last_name" name="last_name" value="{{ old('last_name') }}" type="text" class="input input-bordered w-full" required>
                                @error('last_name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="email">
                                <span class="label-text">Email</span>
                            </label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" required autocomplete="off">
                            @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-control w-full">
                                <label class="label" for="tax_code">
                                    <span class="label-text">Codice fiscale</span>
                                </label>
                                <input id="tax_code" name="tax_code" value="{{ old('tax_code') }}" type="text" class="input input-bordered w-full" required>
                                @error('tax_code') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-control w-full">
                                <label class="label" for="phone">
                                    <span class="label-text">Numero di telefono</span>
                                </label>
                                <input id="phone" name="phone" value="{{ old('phone') }}" type="text" class="input input-bordered w-full" required placeholder="+39 …">
                                @error('phone') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="company_id">
                                <span class="label-text">Azienda (opzionale)</span>
                            </label>
                            <select id="company_id" name="company_id" class="select select-bordered w-full">
                                <option value="">— Nessuna —</option>
                                @foreach(($companies ?? []) as $company)
                                    <option value="{{ $company->id }}" @selected(old('company_id') === $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="password">
                                <span class="label-text">Password (opzionale)</span>
                            </label>
                            <input id="password" name="password" type="password" class="input input-bordered w-full" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 text-sm">
                            <input type="hidden" name="send_credentials_email" value="0">
                            <input type="checkbox" name="send_credentials_email" value="1" class="checkbox checkbox-primary mt-0.5 shrink-0" @checked(old('send_credentials_email', true))>
                            <span>
                                <span class="font-medium">Invia subito email con credenziali</span>
                                <span class="mt-0.5 block text-xs text-base-content/60">URL di accesso e password (consigliato; deseleziona solo se invierai le credenziali in altro modo).</span>
                            </span>
                        </label>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="btn btn-primary">Crea allievo</button>
                            <a href="{{ route('tenant.admin.learners.index') }}" class="btn btn-outline">Annulla</a>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.page>
    </div>
</x-layouts.tenant>
