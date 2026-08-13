<x-layouts.tenant :title="'Allievi — '.$company->name">
    <x-ui.page>
        <x-ui.page-header
            title="Allievi"
            :subtitle="'Azienda: '.$company->name"
        >
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.companies.show', $company) }}" class="link link-hover">{{ $company->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">Allievi</span>
            </x-slot:breadcrumb>
            <x-slot:actions>
                <a href="{{ route('tenant.admin.companies.learners.import', $company) }}" class="btn btn-outline inline-flex items-center gap-2">
                    <i class="ph ph-upload-simple"></i> Importa CSV
                </a>
                <button type="button" class="btn btn-primary inline-flex items-center gap-2" data-modal-open="create-company-learner-modal">
                    <i class="ph ph-user-plus"></i> Nuovo allievo
                </button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="border-b border-base-300 bg-base-200/40 px-4 py-2 lg:px-6">
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="q" value="{{ $q ?? '' }}" type="text" class="input input-bordered input-sm w-full max-w-sm" placeholder="Cerca per nome, cognome o email…">
                <button type="submit" class="btn btn-outline btn-sm">Cerca</button>
                @if (filled($q ?? ''))
                    <a href="{{ route('tenant.admin.companies.learners.index', $company) }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
            </form>
        </div>

        @tenantcan('learners.manage')
            <form method="post"
                  action="{{ route('tenant.admin.learners.send-credentials-bulk') }}"
                  class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 px-4 py-2 text-sm lg:px-6"
                  id="bulkCredentialsForm"
                  onsubmit="return confirm('Vuoi rigenerare e inviare le credenziali a tutti gli allievi selezionati?');">
                @csrf
                <div class="flex items-center gap-3">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" id="selectAllLearners" class="checkbox checkbox-sm">
                        <span class="text-sm font-medium">Seleziona tutti</span>
                    </label>
                    <span class="text-xs text-base-content/60">
                        Selezionati: <span id="selectedCount" class="font-mono">0</span>
                    </span>
                </div>
                <button type="submit" id="bulkSendBtn" class="btn btn-outline btn-sm" disabled>
                    Invia credenziali (selezionati)
                </button>
            </form>
        @endtenantcan

        <div class="overflow-x-auto border-b border-base-300">
            <table class="table table-zebra w-full text-sm">
                <thead>
                    <tr>
                        <th class="w-[52px]"></th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Notturno</th>
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
                            <td>
                                <form method="post" action="{{ route('tenant.admin.learners.night-override', $learner) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="badge {{ $learner->night_hours_override ? 'badge-success' : 'badge-ghost' }} badge-sm cursor-pointer"
                                            title="{{ $learner->night_hours_override ? 'Disattiva override notturno' : 'Attiva override notturno' }}">
                                        {{ $learner->night_hours_override ? 'Sì' : 'No' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-base-content/60">
                                {{ $learner->credentials_sent_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-1">
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
                            <td colspan="6" class="py-10 text-center text-base-content/60">Nessun allievo in questa azienda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 lg:px-6">{{ $learners->links() }}</div>

        <x-ui.modal id="create-company-learner-modal" title="Nuovo allievo" intent="create_company_learner" size="lg">
            @include('tenant.admin.companies.learners.partials.create-form')
            <x-slot:footer>
                <form method="dialog" data-no-loader>
                    <button type="submit" class="btn btn-ghost">Annulla</button>
                </form>
                <button type="submit" form="create-company-learner-form" class="btn btn-primary">Crea allievo</button>
            </x-slot:footer>
        </x-ui.modal>
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
