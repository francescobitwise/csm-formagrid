<x-layouts.tenant :title="'Aziende — '.tenant('id')">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="admin-hero mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Aziende</h1>
                    <p class="admin-subtitle">Gestisci le aziende e associa i corsisti per report e assegnazioni corsi.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tenant.admin.companies.create') }}" class="admin-btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-buildings"></i> Nuova azienda
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

            <form method="get" class="mb-4 flex flex-wrap items-center gap-3">
                <input name="q" value="{{ $q ?? '' }}" type="text" class="form-input max-w-sm" placeholder="Cerca azienda…">
                <button type="submit" class="admin-btn-secondary">Cerca</button>
                @if (filled($q ?? ''))
                    <a href="{{ route('tenant.admin.companies.index') }}" class="admin-btn-secondary">Reset</a>
                @endif
            </form>

            <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">P.IVA</th>
                            <th class="px-4 py-3">Allievi</th>
                            <th class="px-4 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($companies as $company)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-white">
                                    <a href="{{ route('tenant.admin.companies.show', $company) }}" class="hover:text-brand-amber">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-400 font-mono">{{ $company->vat ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ (int) ($company->users_count ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-3">
                                        @tenantcan('learners.manage')
                                            <a href="{{ route('tenant.admin.companies.learners.index', $company) }}" class="text-xs font-semibold text-slate-300 hover:text-white">Allievi</a>
                                        @endtenantcan
                                        <a href="{{ route('tenant.admin.companies.edit', $company) }}" class="text-xs font-semibold text-brand-blue hover:text-brand-amber">Modifica</a>
                                        <form method="post" action="{{ route('tenant.admin.companies.destroy', $company) }}" class="inline" onsubmit="return confirm('Eliminare definitivamente questa azienda?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-xs font-semibold text-rose-400/90 hover:text-rose-300">Elimina</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">Nessuna azienda. Crea la prima azienda per iniziare.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $companies->links() }}</div>
        </div>
    </div>
</x-layouts.tenant>

