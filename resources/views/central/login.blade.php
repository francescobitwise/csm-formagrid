<x-layouts.central
    :title="config('app.name').' — Accesso amministratore'"
    description="Accesso riservato agli amministratori della piattaforma centrale (onboarding e gestione organizzazioni)."
>
    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-2xl font-bold text-white">Amministratore</h1>

        <form method="post" action="{{ route('central.login.store') }}" class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-slate-900/50 p-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-300" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white placeholder:text-slate-600 focus:border-brand-blue/50 focus:outline-none focus:ring-2 focus:ring-brand-blue/30">
                @error('email')
                    <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300" for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white placeholder:text-slate-600 focus:border-brand-blue/50 focus:outline-none focus:ring-2 focus:ring-brand-blue/30">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-600" @checked(old('remember'))>
                Ricordami su questo dispositivo
            </label>
            <button type="submit" class="w-full rounded-xl bg-brand-blue py-3 text-sm font-semibold text-white transition hover:bg-brand-navy active:scale-95">
                Accedi
            </button>
        </form>
    </div>
</x-layouts.central>
