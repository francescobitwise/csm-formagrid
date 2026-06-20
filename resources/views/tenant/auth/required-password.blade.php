<x-layouts.tenant :title="'Nuova password — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-4 py-16">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-wider text-warning">Sicurezza</div>
                    <h1 class="mt-2 text-xl font-semibold">Scegli la tua password</h1>
                    <p class="mt-2 text-sm text-base-content/70">
                        È il tuo primo accesso con una password assegnata dall’organizzazione. Imposta una password personale per continuare.
                    </p>
                    <p class="mt-2 text-sm text-base-content/60">
                        Account <span class="font-mono">{{ $email }}</span>
                    </p>
                </div>

                <form method="post" action="{{ route('tenant.password.required.update') }}" class="space-y-5">
                    @csrf

                    <div class="form-control w-full">
                        <label class="label" for="password">
                            <span class="label-text font-medium">Nuova password</span>
                        </label>
                        <input id="password" name="password" type="password" class="input input-bordered w-full" placeholder="Almeno 8 caratteri" required autofocus autocomplete="new-password">
                        @error('password')
                            <x-ui.field-error :message="$message" />
                        @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label" for="password_confirmation">
                            <span class="label-text font-medium">Conferma password</span>
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input input-bordered w-full" placeholder="Ripeti la password" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-full">Salva e continua</button>
                </form>

                <form method="post" action="{{ route('tenant.logout') }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm w-full text-base-content/60">
                        Esci e torna al login
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.tenant>
