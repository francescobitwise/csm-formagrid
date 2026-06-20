<x-layouts.tenant :title="'Accedi — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-4 py-16">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Organizzazione</div>
                        <div class="mt-1 text-lg font-semibold">{{ tenant('organization_name') }}</div>
                    </div>
                    <span class="badge badge-ghost shrink-0">Login</span>
                </div>

                <form method="post" action="{{ route('tenant.login.store') }}" class="space-y-5">
                    @csrf

                    <div class="form-control w-full">
                        <label class="label" for="email">
                            <span class="label-text font-medium">Email</span>
                        </label>
                        <input id="email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" placeholder="nome@azienda.it" required autofocus>
                        @error('email')
                            <x-ui.field-error :message="$message" />
                        @enderror
                    </div>

                    <div class="form-control w-full">
                        <div class="label">
                            <label class="label-text font-medium" for="password">Password</label>
                            <a href="{{ route('tenant.password.request') }}" class="label-text-alt link link-primary text-xs">Password dimenticata?</a>
                        </div>
                        <input id="password" name="password" type="password" class="input input-bordered w-full" placeholder="••••••••" required autocomplete="current-password">
                        @error('password')
                            <x-ui.field-error :message="$message" />
                        @enderror
                    </div>

                    <label class="label cursor-pointer justify-start gap-2">
                        <input name="remember" type="checkbox" class="checkbox checkbox-primary checkbox-sm">
                        <span class="label-text">Ricordami</span>
                    </label>

                    <button type="submit" class="btn btn-primary w-full">Accedi</button>
                </form>

                <p class="mt-6 text-center text-sm text-base-content/60">
                    L’accesso è gestito dall’amministratore della tua organizzazione. Hai dimenticato la password?
                    <a href="{{ route('tenant.password.request') }}" class="link link-primary font-medium">Recuperala</a>
                    <span class="mt-2 block text-xs">Problemi di accesso? Contatta il tuo amministratore.</span>
                </p>
            </div>
        </div>
    </div>
</x-layouts.tenant>
