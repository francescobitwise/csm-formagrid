<x-layouts.tenant :title="'Allievi — '.tenant('id')">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="admin-hero mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Allievi</h1>
                    <p class="admin-subtitle">Gestisci gli account allievi della tua organizzazione: creazione manuale, import CSV e invio credenziali via email.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tenant.admin.learners.import') }}" class="admin-btn-secondary inline-flex items-center gap-2">
                        <i class="ph ph-upload-simple"></i> Importa CSV
                    </a>
                    <a href="{{ route('tenant.admin.learners.create') }}" class="admin-btn-primary inline-flex items-center gap-2">
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

            <form method="post" action="{{ route('tenant.admin.learners.send-credentials-bulk') }}" id="learner-bulk-credentials" class="mb-4">
                @csrf
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" id="learner-send-credentials-bulk-btn" disabled
                            class="admin-btn-secondary inline-flex items-center gap-2 border-brand-blue/35 bg-brand-blue/10 font-semibold hover:bg-brand-blue/15"
                            onclick="return confirm('Verrà generata una nuova password per ogni allievo selezionato e inviata via email. Continuare?');">
                        <i class="ph ph-paper-plane-tilt"></i>
                        Invia credenziali (selezionati)
                    </button>
                    <span class="text-xs text-slate-500">Rigenera password e invia email a più allievi.</span>
                </div>
            </form>

                <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="w-10 px-4 py-3"></th>
                                <th class="px-4 py-3">Nome</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Azienda</th>
                                <th class="px-4 py-3">Credenziali inviate</th>
                                <th class="px-4 py-3 text-right">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($learners as $learner)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" form="learner-bulk-credentials" class="h-4 w-4 rounded border-slate-600">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-white">{{ $learner->name }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ $learner->email }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ $learner->company?->name ?? '—' }}</td>
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
                                            <form method="post" action="{{ route('tenant.admin.learners.destroy', $learner) }}" class="inline" onsubmit="return confirm('Eliminare definitivamente questo allievo e i dati associati nel LMS? Per export GDPR usa prima Admin → Compliance. Confermi?');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="text-xs font-semibold text-rose-400/90 hover:text-rose-300">Elimina</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-slate-500">Nessun allievo. Importa un CSV o crea un account manualmente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <div class="mt-6">{{ $learners->links() }}</div>
        </div>
    </div>
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
