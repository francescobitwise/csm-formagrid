<x-layouts.tenant :title="'Import allievi CSV — '.$company->name">
    <x-ui.page>
        <x-ui.page-header
            title="Import da CSV"
            :subtitle="'Azienda: '.$company->name.'. Gli account creati verranno associati automaticamente a questa azienda.'"
        >
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.companies.learners.index', $company) }}" class="link link-hover">Allievi</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">Import CSV</span>
            </x-slot:breadcrumb>
        </x-ui.page-header>

        <div class="space-y-0 border-b border-base-300">
            <div class="border-b border-base-300 px-4 py-6 text-sm text-base-content/70 lg:px-6">
                <div class="mx-auto max-w-2xl space-y-4">
                    <p class="font-medium text-base-content">Colonne supportate</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li><code class="rounded bg-base-200 px-1 text-accent">first_name</code> (obbligatoria) — accettati anche <code class="rounded bg-base-200 px-1">nome</code></li>
                        <li><code class="rounded bg-base-200 px-1 text-accent">last_name</code> (obbligatoria) — accettati anche <code class="rounded bg-base-200 px-1">cognome</code></li>
                        <li><code class="rounded bg-base-200 px-1 text-accent">email</code> (obbligatoria) — accettati anche <code class="rounded bg-base-200 px-1">mail</code>, <code class="rounded bg-base-200 px-1">e-mail</code></li>
                        <li><code class="rounded bg-base-200 px-1 text-accent">tax_code</code> (obbligatoria) — accettati anche <code class="rounded bg-base-200 px-1">codice fiscale</code>, <code class="rounded bg-base-200 px-1">cf</code></li>
                        <li><code class="rounded bg-base-200 px-1 text-accent">phone</code> (obbligatoria) — accettati anche <code class="rounded bg-base-200 px-1">telefono</code>, <code class="rounded bg-base-200 px-1">cellulare</code></li>
                        <li><code class="rounded bg-base-200 px-1 text-accent">password</code> (opzionale) — se vuota viene generata; minimo 8 caratteri se indicata</li>
                    </ul>
                    <p class="border border-base-300 bg-base-200/50 p-3 font-mono text-xs">
                        first_name,last_name,email,tax_code,phone,password<br>
                        Mario,Rossi,mario@azienda.it,MRARSS80A01H501U,+391234567890,<br>
                        Luisa,Bianchi,luisa@azienda.it,LISBNC80A01H501U,+39111222333,
                    </p>
                    <p class="text-xs text-base-content/60">Le email già presenti vengono saltate (riga segnalata nel riepilogo).</p>
                </div>
            </div>

            <div class="px-4 py-6 lg:px-6">
                <form method="post"
                      action="{{ route('tenant.admin.companies.learners.import.store', $company) }}"
                      enctype="multipart/form-data"
                      class="mx-auto max-w-2xl space-y-5">
                    @csrf

                    <div class="form-control w-full">
                        <label class="label" for="csv_file">
                            <span class="label-text">File CSV</span>
                        </label>
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt,text/csv" class="file-input file-input-bordered w-full" required>
                        @error('csv_file') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 text-sm">
                        <input type="hidden" name="send_credentials_email" value="0">
                        <input type="checkbox" name="send_credentials_email" value="1" class="checkbox checkbox-primary mt-0.5 shrink-0" @checked(old('send_credentials_email', true))>
                        <span>
                            <span class="font-medium">Invia subito email con credenziali</span>
                            <span class="mt-0.5 block text-xs text-base-content/60">URL di accesso e password (consigliato).</span>
                        </span>
                    </label>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary">Importa CSV</button>
                        <a href="{{ route('tenant.admin.companies.learners.index', $company) }}" class="btn btn-outline">Annulla</a>
                    </div>
                </form>
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
