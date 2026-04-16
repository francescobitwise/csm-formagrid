<x-layouts.tenant :title="'Allievi — '.$company->name">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.companies.show', $company) }}" class="text-sm text-slate-400 hover:text-white">&larr; {{ $company->name }}</a>

            <div class="admin-hero mb-8 mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Allievi</h1>
                    <p class="admin-subtitle">Azienda: <span class="text-slate-200">{{ $company->name }}</span></p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tenant.admin.companies.learners.import', $company) }}" class="admin-btn-secondary inline-flex items-center gap-2">
                        <i class="ph ph-upload-simple"></i> Importa CSV
                    </a>
                    <a href="{{ route('tenant.admin.companies.learners.create', $company) }}" class="admin-btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-user-plus"></i> Nuovo allievo
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    @foreach ($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            @tenantcan('learners.manage')
                <form method="post"
                      action="{{ route('tenant.admin.learners.send-credentials-bulk') }}"
                      class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"
                      id="bulkCredentialsForm"
                      onsubmit="return confirm('Vuoi rigenerare e inviare le credenziali a tutti gli allievi selezionati?');">
                    @csrf
                    <div class="flex items-center gap-3 text-slate-300">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" id="selectAllLearners" class="h-4 w-4 rounded border-slate-600 bg-slate-950/50">
                            <span class="text-sm font-medium text-slate-200">Seleziona tutti</span>
                        </label>
                        <span class="text-xs text-slate-500">
                            Selezionati: <span id="selectedCount" class="font-mono text-slate-300">0</span>
                        </span>
                    </div>
                    <button type="submit" id="bulkSendBtn" class="admin-btn-secondary" disabled>
                        Invia credenziali (selezionati)
                    </button>
                </form>
            @endtenantcan

            <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 w-[52px]"></th>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Credenziali inviate</th>
                            <th class="px-4 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($learners as $learner)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           form="bulkCredentialsForm"
                                           name="learner_ids[]"
                                           value="{{ $learner->id }}"
                                           class="learner-row-checkbox h-4 w-4 rounded border-slate-600 bg-slate-950/50">
                                </td>
                                <td class="px-4 py-3 font-medium text-white">{{ $learner->name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $learner->email }}</td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ $learner->credentials_sent_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <form method="post" action="{{ route('tenant.admin.learners.send-credentials', $learner) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-brand-blue hover:text-brand-amber"
                                                    onclick="return confirm('Generare una nuova password e inviarla a {{ $learner->email }}?');">
                                                Invia credenziali
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('tenant.admin.learners.destroy', $learner) }}" class="inline"
                                              onsubmit="return confirm('Eliminare definitivamente questo allievo?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-xs font-semibold text-rose-400/90 hover:text-rose-300">Elimina</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500">Nessun allievo in questa azienda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $learners->links() }}</div>
        </div>
    </div>
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

