<x-layouts.tenant :title="'Nuova password — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-4 py-16">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Recupero accesso</div>
                    <h1 class="mt-2 text-xl font-semibold">Imposta una nuova password</h1>
                    <p class="mt-2 text-sm text-base-content/70">Scegli una password sicura per l’account <span class="font-mono">{{ $email }}</span>.</p>
                </div>

                <form method="post" action="{{ route('tenant.password.update') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

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

                    @error('email')
                        <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                    @enderror

                    <button type="submit" class="btn btn-primary w-full">Salva password</button>
                </form>

                <p class="mt-6 text-center text-sm text-base-content/70">
                    <a href="{{ route('tenant.login') }}" class="link link-primary font-medium">Vai al login</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.tenant>
