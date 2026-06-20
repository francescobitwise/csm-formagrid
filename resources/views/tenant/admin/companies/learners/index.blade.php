<x-layouts.tenant :title="'Allievi — '.$company->name">
    <x-ui.page>
        <a href="{{ route('tenant.admin.companies.show', $company) }}" class="link link-hover text-sm text-base-content/70">&larr; {{ $company->name }}</a>

        <x-ui.page-header
            title="Allievi"
            :subtitle="'Azienda: '.$company->name"
            class="mt-4"
        >
            <x-slot:actions>
                <a href="{{ route('tenant.admin.companies.learners.import', $company) }}" class="btn btn-outline inline-flex items-center gap-2">
                    <i class="ph ph-upload-simple"></i> Importa CSV
                </a>
                <a href="{{ route('tenant.admin.companies.learners.create', $company) }}" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-user-plus"></i> Nuovo allievo
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @tenantcan('learners.manage')
            <form method="post"
                  action="{{ route('tenant.admin.learners.send-credentials-bulk') }}"
                  class="card bg-base-100 shadow-lg mb-4"
                  id="bulkCredentialsForm"
                  onsubmit="return confirm('Vuoi rigenerare e inviare le credenziali a tutti gli allievi selezionati?');">
                @csrf
                <div class="card-body flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <div class="flex items-center gap-3">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" id="selectAllLearners" class="checkbox checkbox-sm">
                            <span class="text-sm font-medium">Seleziona tutti</span>
                        </label>
                        <span class="text-xs text-base-content/60">
                            Selezionati: <span id="selectedCount" class="font-mono">0</span>
                        </span>
                    </div>
                    <button type="submit" id="bulkSendBtn" class="btn btn-outline" disabled>
                        Invia credenziali (selezionati)
                    </button>
                </div>
            </form>
        @endtenantcan

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th class="w-[52px]"></th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Credenziali inviate</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($learners as $learner)
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           form="bulkCredentialsForm"
                                           name="learner_ids[]"
                                           value="{{ $learner->id }}"
                                           class="learner-row-checkbox checkbox checkbox-sm">
                                </td>
                                <td class="font-medium">{{ $learner->name }}</td>
                                <td class="text-base-content/70">{{ $learner->email }}</td>
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
                                        <form method="post" action="{{ route('tenant.admin.learners.destroy', $learner) }}" class="inline"
                                              onsubmit="return confirm('Eliminare definitivamente questo allievo?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Elimina</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-base-content/60">Nessun allievo in questa azienda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $learners->links() }}</div>
    </x-ui.page>
</x-layouts.tenant>

<script>
    (() => {
        const form = document.getElementById('bulkCredentialsForm');
        if (!form) return;

        const selectAll = document.getElementById('selectAllLearners');
        const checkboxes = Array.from(document.querySelectorAll('.learner-row-checkbox'));
        const countEl = document.getElementById('selectedCount');
        const btn = document.getElementById('bulkSendBtn');

        const update = () => {
            const selected = checkboxes.filter(c => c.checked).length;
            if (countEl) countEl.textContent = String(selected);
            if (btn) btn.disabled = selected === 0;
            if (selectAll) {
                selectAll.checked = selected > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const next = !!selectAll.checked;
                checkboxes.forEach(c => { c.checked = next; });
                update();
            });
        }

        checkboxes.forEach(c => c.addEventListener('change', update));
        update();
    })();
</script>
