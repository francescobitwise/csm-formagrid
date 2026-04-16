<x-layouts.tenant :title="'Accedi — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Organizzazione</div>
                    <div class="mt-1 text-lg font-semibold text-white">{{ tenant('organization_name') }}</div>
                </div>
                <span class="shrink-0 rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-300">Login</span>
            </div>

            <form method="post" action="{{ route('tenant.login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" value="{{ old('email') }}" type="email" class="form-input" placeholder="nome@azienda.it" required autofocus>
                    @error('email') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between gap-2">
                        <label class="form-label mb-0" for="password">Password</label>
                        <a href="{{ route('tenant.password.request') }}" class="text-xs font-medium text-brand-blue hover:text-brand-amber">Password dimenticata?</a>
                    </div>
                    <input id="password" name="password" type="password" class="form-input mt-1.5" placeholder="••••••••" required autocomplete="current-password">
                    @error('password') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-700 bg-slate-950/50">
                    Ricordami
                </label>

                <button class="w-full rounded-xl bg-brand-blue px-4 py-3 font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    Accedi
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                L’accesso è gestito dall’amministratore della tua organizzazione. Hai dimenticato la password?
                <a href="{{ route('tenant.password.request') }}" class="font-medium text-brand-blue hover:text-brand-amber">Recuperala</a>
                <span class="block pt-2 text-xs text-slate-500">Problemi di accesso? Contatta il tuo amministratore.</span>
            </p>
        </div>
    </div>
</x-layouts.tenant>

