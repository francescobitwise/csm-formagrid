<x-layouts.tenant :title="'Import allievi CSV — '.tenant('id')">
    <div class="mx-auto max-w-2xl px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.learners.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Allievi</a>
            <h1 class="admin-title mt-4">Import da CSV</h1>
            <p class="admin-subtitle mt-1">File UTF-8 con intestazione sulla prima riga.</p>
            @if (($learnerQuotaRemaining ?? null) !== null)
                <p class="mt-2 text-sm {{ ($learnerQuotaRemaining ?? 0) === 0 ? 'text-amber-300' : 'text-slate-500' }}">
                    @if (($learnerQuotaRemaining ?? 0) === 0)
                        Limite allievi del piano raggiunto: l’import non potrà creare nuovi account.
                    @else
                        Puoi creare al massimo ancora <strong class="text-slate-300">{{ $learnerQuotaRemaining }}</strong> allievi con questo piano.
                    @endif
                </p>
            @endif

            <div class="glass-card mt-6 space-y-4 rounded-xl border border-white/5 p-6 text-sm text-slate-400">
                <p class="font-medium text-slate-200">Colonne supportate</p>
                <ul class="list-inside list-disc space-y-1">
                    <li><code class="rounded bg-slate-900 px-1 text-brand-amber/90">email</code> (obbligatoria) — accettati anche <code class="rounded bg-slate-900 px-1">mail</code>, <code class="rounded bg-slate-900 px-1">e-mail</code></li>
                    <li><code class="rounded bg-slate-900 px-1 text-brand-amber/90">name</code> (opzionale) — oppure <code class="rounded bg-slate-900 px-1">nome</code></li>
                    <li><code class="rounded bg-slate-900 px-1 text-brand-amber/90">password</code> (opzionale) — se vuota viene generata; minimo 8 caratteri se indicata</li>
                </ul>
                <p class="rounded-lg border border-white/10 bg-slate-950/50 p-3 font-mono text-xs text-slate-300">
                    name,email,password<br>
                    Mario Rossi,mario@azienda.it,<br>
                    Luisa Bianchi,luisa@azienda.it,
                </p>
                <p class="text-xs text-slate-500">Le email già presenti in questa organizzazione vengono saltate (riga segnalata nel riepilogo).</p>
            </div>

            <form method="post" action="{{ route('tenant.admin.learners.import.store') }}" enctype="multipart/form-data" class="glass-card mt-6 space-y-5 rounded-xl border border-white/5 p-6">
                @csrf

                <div>
                    <label class="form-label" for="csv_file">File CSV</label>
                    <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt,text/csv" class="form-input" required>
                    @error('csv_file') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="hidden" name="send_credentials_email" value="0">
                    <input type="checkbox" name="send_credentials_email" value="1" class="h-4 w-4 rounded border-slate-600" checked>
                    Invia email con credenziali a ogni nuovo allievo importato
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Avvia import</button>
                    <a href="{{ route('tenant.admin.learners.index') }}" class="admin-btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.tenant>
