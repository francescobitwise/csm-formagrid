<x-layouts.tenant :title="'Nuova password — '.tenant('id')">
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8">
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Recupero accesso</div>
                <h1 class="mt-2 text-xl font-semibold text-white">Imposta una nuova password</h1>
                <p class="mt-2 text-sm text-slate-400">Scegli una password sicura per l’account <span class="font-mono text-slate-300">{{ $email }}</span>.</p>
            </div>

            <form method="post" action="{{ route('tenant.password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label class="form-label" for="password">Nuova password</label>
                    <input id="password" name="password" type="password" class="form-input" placeholder="Almeno 8 caratteri" required autofocus autocomplete="new-password">
                    @error('password') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label" for="password_confirmation">Conferma password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" placeholder="Ripeti la password" required autocomplete="new-password">
                </div>

                @error('email') <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">{{ $message }}</div> @enderror

                <button class="w-full rounded-xl bg-brand-blue px-4 py-3 font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    Salva password
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                <a href="{{ route('tenant.login') }}" class="font-medium text-brand-blue hover:text-brand-amber">Vai al login</a>
            </p>
        </div>
    </div>
</x-layouts.tenant>
