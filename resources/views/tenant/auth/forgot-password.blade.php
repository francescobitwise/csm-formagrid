<x-layouts.tenant :title="'Password dimenticata — '.tenant('organization_name')">
    <div class="mx-auto w-full max-w-md px-4 py-16">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Recupero accesso</div>
                    <h1 class="mt-2 text-xl font-semibold">Password dimenticata</h1>
                    <p class="mt-2 text-sm text-base-content/70">Inserisci l’email del tuo account: se è registrata riceverai un link per impostare una nuova password.</p>
                </div>

                <form method="post" action="{{ route('tenant.password.email') }}" class="space-y-5">
                    @csrf

                    <div class="form-control w-full">
                        <label class="label" for="email">
                            <span class="label-text font-medium">Email</span>
                        </label>
                        <input id="email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" placeholder="nome@azienda.it" required autofocus autocomplete="email">
                        @error('email')
                            <x-ui.field-error :message="$message" />
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full">Invia link di reset</button>
                </form>

                <p class="mt-6 text-center text-sm text-base-content/70">
                    <a href="{{ route('tenant.login') }}" class="link link-primary font-medium">&larr; Torna al login</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.tenant>
