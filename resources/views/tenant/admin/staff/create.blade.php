<x-layouts.tenant :title="'Nuovo staff — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.staff.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Staff</a>
            <h1 class="admin-title mt-4">Nuovo utente staff</h1>
            <p class="admin-subtitle mt-1">Scegli <strong>Amministratore</strong> per accesso completo, o <strong>Istruttore</strong> per solo contenuti (corsi in lettura, moduli in lettura, gestione lezioni e upload video).</p>

            <form method="post" action="{{ route('tenant.admin.staff.store') }}" class="glass-card mt-6 space-y-5 rounded-xl border border-white/5 p-6">
                @csrf

                <div>
                    <label class="form-label" for="name">Nome e cognome</label>
                    <input id="name" name="name" value="{{ old('name') }}" type="text" class="form-input" required>
                    @error('name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" value="{{ old('email') }}" type="email" class="form-input" required autocomplete="off">
                    @error('email') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="role">Ruolo</label>
                    <select id="role" name="role" class="form-input">
                        <option value="{{ \App\Enums\UserRole::Instructor->value }}" @selected(old('role', \App\Enums\UserRole::Instructor->value) === \App\Enums\UserRole::Instructor->value)>Istruttore (solo contenuti)</option>
                        <option value="{{ \App\Enums\UserRole::Admin->value }}" @selected(old('role') === \App\Enums\UserRole::Admin->value)>Amministratore</option>
                    </select>
                    @error('role') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="password">Password (opzionale)</label>
                    <input id="password" name="password" type="password" class="form-input" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
                    @error('password') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="hidden" name="send_credentials_email" value="0">
                    <input type="checkbox" name="send_credentials_email" value="1" class="h-4 w-4 rounded border-slate-600" @checked(old('send_credentials_email'))>
                    Invia subito email con credenziali di accesso
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Crea utente</button>
                    <a href="{{ route('tenant.admin.staff.index') }}" class="admin-btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.tenant>
