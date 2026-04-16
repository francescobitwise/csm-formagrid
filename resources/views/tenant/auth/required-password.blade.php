<x-layouts.tenant :title="'Nuova password — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8">
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-400/90">Sicurezza</div>
                <h1 class="mt-2 text-xl font-semibold text-white">Scegli la tua password</h1>
                <p class="mt-2 text-sm text-slate-400">
                    È il tuo primo accesso con una password assegnata dall’organizzazione. Imposta una password personale per continuare.
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    Account <span class="font-mono text-slate-300">{{ $email }}</span>
                </p>
            </div>

            <form method="post" action="{{ route('tenant.password.required.update') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="form-label" for="password">Nuova password</label>
                    <input id="password" name="password" type="password" class="form-input" placeholder="Almeno 8 caratteri" required autofocus autocomplete="new-password">
                    @error('password') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label" for="password_confirmation">Conferma password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" placeholder="Ripeti la password" required autocomplete="new-password">
                </div>

                <button class="w-full rounded-xl bg-brand-blue px-4 py-3 font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    Salva e continua
                </button>
            </form>

            <form method="post" action="{{ route('tenant.logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full text-center text-sm text-slate-500 transition hover:text-slate-300">
                    Esci e torna al login
                </button>
            </form>
        </div>
    </div>
</x-layouts.tenant>
