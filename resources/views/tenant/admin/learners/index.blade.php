<x-layouts.tenant :title="'Allievi — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Allievi"
            subtitle="Gestisci gli account allievi della tua organizzazione: creazione manuale, import CSV e invio credenziali via email."
        >
            <x-slot:actions>
                <a href="{{ route('tenant.admin.learners.import') }}" class="btn btn-outline inline-flex items-center gap-2">
                    <i class="ph ph-upload-simple"></i> Importa CSV
                </a>
                <a href="{{ route('tenant.admin.learners.create') }}" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-user-plus"></i> Nuovo allievo
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="post" action="{{ route('tenant.admin.learners.send-credentials-bulk') }}" id="learner-bulk-credentials" class="mb-4">
            @csrf
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" id="learner-send-credentials-bulk-btn" disabled
                        class="btn btn-outline inline-flex items-center gap-2"
                        onclick="return confirm('Verrà generata una nuova password per ogni allievo selezionato e inviata via email. Continuare?');">
                    <i class="ph ph-paper-plane-tilt"></i>
                    Invia credenziali (selezionati)
                </button>
                <span class="text-xs text-base-content/60">Rigenera password e invia email a più allievi.</span>
            </div>
        </form>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Codice fiscale</th>
                            <th>Telefono</th>
                            <th>Azienda</th>
                            <th>Credenziali inviate</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($learners as $learner)
                            <tr>
                                <td>
                                    <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" form="learner-bulk-credentials" class="checkbox checkbox-sm">
                                </td>
                                <td class="font-medium">{{ $learner->displayName() }}</td>
                                <td class="text-base-content/70">{{ $learner->email }}</td>
                                <td class="font-mono text-base-content/70">{{ $learner->tax_code ?? '—' }}</td>
                                <td class="text-base-content/70">{{ $learner->phone ?? '—' }}</td>
                                <td class="text-base-content/70">{{ $learner->company?->name ?? '—' }}</td>
                                <td class="text-base-content/60">
                                    {{ $learner->credentials_sent_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <form method="post" action="{{ route('tenant.admin.learners.send-credentials', $learner) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-xs text-primary"
                                                    onclick="return confirm('Generare una nuova password e inviarla a {{ $learner->email }}?');">
                                                Invia credenziali
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('tenant.admin.learners.destroy', $learner) }}" class="inline" onsubmit="return confirm('Eliminare definitivamente questo allievo e i dati associati nel LMS? Per export GDPR usa prima Admin → Compliance. Confermi?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Elimina</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-base-content/60">Nessun allievo. Importa un CSV o crea un account manualmente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $learners->links() }}</div>
    </x-ui.page>
</x-layouts.tenant>

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('learner-bulk-credentials');
            const btn = document.getElementById('learner-send-credentials-bulk-btn');
            if (!form || !btn) return;

            function sync() {
                const checked = document.querySelectorAll("input[name='learner_ids[]']:checked").length;
                btn.disabled = checked === 0;
            }

            document.addEventListener('change', (e) => {
                const t = e.target;
                if (t && t.matches && t.matches("input[name='learner_ids[]']")) {
                    sync();
                }
            });

            sync();
        })();
    </script>
@endpush
