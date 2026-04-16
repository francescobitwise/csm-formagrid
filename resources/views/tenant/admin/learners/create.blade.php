<x-layouts.tenant :title="'Nuovo allievo — '.tenant('id')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.learners.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Allievi</a>
            <h1 class="admin-title mt-4">Nuovo allievo</h1>
            <p class="admin-subtitle mt-1">Ruolo learner. Puoi lasciare vuota la password per generarne una casuale.</p>
            @if (! ($canAddLearner ?? true))
                <div class="mt-4 rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                    Hai raggiunto il numero massimo di allievi previsto dal piano. Contatta l’amministratore della piattaforma per un upgrade.
                </div>
            @endif

            <form method="post" action="{{ route('tenant.admin.learners.store') }}" class="glass-card mt-6 space-y-5 rounded-xl border border-white/5 p-6">
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
                    <label class="form-label" for="password">Password (opzionale)</label>
                    <input id="password" name="password" type="password" class="form-input" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
                    @error('password') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <label class="flex cursor-pointer items-start gap-3 text-sm text-slate-300">
                    <input type="hidden" name="send_credentials_email" value="0">
                    <input type="checkbox" name="send_credentials_email" value="1" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-600" @checked(old('send_credentials_email', true))>
                    <span>
                        <span class="font-medium text-slate-200">Invia subito email con credenziali</span>
                        <span class="mt-0.5 block text-xs text-slate-500">URL di accesso e password (consigliato; deseleziona solo se invierai le credenziali in altro modo).</span>
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary" @disabled(! ($canAddLearner ?? true))>Crea allievo</button>
                    <a href="{{ route('tenant.admin.learners.index') }}" class="admin-btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.tenant>
