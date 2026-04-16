<x-layouts.tenant :title="'Password dimenticata — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8">
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Recupero accesso</div>
                <h1 class="mt-2 text-xl font-semibold text-white">Password dimenticata</h1>
                <p class="mt-2 text-sm text-slate-400">Inserisci l’email del tuo account: se è registrata riceverai un link per impostare una nuova password.</p>
            </div>

            <form method="post" action="{{ route('tenant.password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" value="{{ old('email') }}" type="email" class="form-input" placeholder="nome@azienda.it" required autofocus autocomplete="email">
                    @error('email') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <button class="w-full rounded-xl bg-brand-blue px-4 py-3 font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    Invia link di reset
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                <a href="{{ route('tenant.login') }}" class="font-medium text-brand-blue hover:text-brand-amber">&larr; Torna al login</a>
            </p>
        </div>
    </div>
</x-layouts.tenant>
