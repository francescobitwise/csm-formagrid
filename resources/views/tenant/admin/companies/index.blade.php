<x-layouts.tenant :title="'Aziende — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Aziende"
            subtitle="Gestisci le aziende e associa i corsisti per report e assegnazioni corsi."
        >
            <x-slot:actions>
                <a href="{{ route('tenant.admin.companies.create') }}" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-buildings"></i> Nuova azienda
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="get" class="mb-4 flex flex-wrap items-center gap-3">
            <input name="q" value="{{ $q ?? '' }}" type="text" class="input input-bordered w-full max-w-sm" placeholder="Cerca azienda…">
            <button type="submit" class="btn btn-outline">Cerca</button>
            @if (filled($q ?? ''))
                <a href="{{ route('tenant.admin.companies.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </form>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>P.IVA</th>
                            <th>Allievi</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td class="font-medium">
                                    <a href="{{ route('tenant.admin.companies.show', $company) }}" class="link link-hover link-primary">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td class="font-mono text-base-content/70">{{ $company->vat ?: '—' }}</td>
                                <td class="text-base-content/70">{{ (int) ($company->users_count ?? 0) }}</td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-3">
                                        @tenantcan('learners.manage')
                                            <a href="{{ route('tenant.admin.companies.learners.index', $company) }}" class="btn btn-ghost btn-xs">Allievi</a>
                                        @endtenantcan
                                        <a href="{{ route('tenant.admin.companies.edit', $company) }}" class="btn btn-ghost btn-xs text-primary">Modifica</a>
                                        <form method="post" action="{{ route('tenant.admin.companies.destroy', $company) }}" class="inline" onsubmit="return confirm('Eliminare definitivamente questa azienda?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Elimina</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-base-content/60">Nessuna azienda. Crea la prima azienda per iniziare.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $companies->links() }}</div>
    </x-ui.page>
</x-layouts.tenant>
