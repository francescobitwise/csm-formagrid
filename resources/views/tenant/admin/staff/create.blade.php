<x-layouts.tenant :title="'Nuovo staff — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <x-ui.page>
            <a href="{{ route('tenant.admin.staff.index') }}" class="link link-hover text-sm">&larr; Staff</a>

            <div class="mt-4">
                <x-ui.page-header title="Nuovo utente staff" subtitle="Scegli Amministratore per accesso completo, o Istruttore per solo contenuti (corsi in lettura, moduli in lettura, gestione lezioni e upload video)." />
            </div>

            <div class="card bg-base-100 shadow-xl mt-6">
                <div class="card-body">
                    <form method="post" action="{{ route('tenant.admin.staff.store') }}" class="space-y-5">
                        @csrf

                        <div class="form-control w-full">
                            <label class="label" for="name">
                                <span class="label-text">Nome e cognome</span>
                            </label>
                            <input id="name" name="name" value="{{ old('name') }}" type="text" class="input input-bordered w-full" required>
                            @error('name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="email">
                                <span class="label-text">Email</span>
                            </label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" required autocomplete="off">
                            @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="role">
                                <span class="label-text">Ruolo</span>
                            </label>
                            <select id="role" name="role" class="select select-bordered w-full">
                                <option value="{{ \App\Enums\UserRole::Instructor->value }}" @selected(old('role', \App\Enums\UserRole::Instructor->value) === \App\Enums\UserRole::Instructor->value)>Istruttore (solo contenuti)</option>
                                <option value="{{ \App\Enums\UserRole::Admin->value }}" @selected(old('role') === \App\Enums\UserRole::Admin->value)>Amministratore</option>
                            </select>
                            @error('role') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="password">
                                <span class="label-text">Password (opzionale)</span>
                            </label>
                            <input id="password" name="password" type="password" class="input input-bordered w-full" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="send_credentials_email" value="0">
                            <input type="checkbox" name="send_credentials_email" value="1" class="checkbox checkbox-primary" @checked(old('send_credentials_email'))>
                            Invia subito email con credenziali di accesso
                        </label>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="btn btn-primary">Crea utente</button>
                            <a href="{{ route('tenant.admin.staff.index') }}" class="btn btn-outline">Annulla</a>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.page>
    </div>
</x-layouts.tenant>
