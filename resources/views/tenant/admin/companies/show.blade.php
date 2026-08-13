<x-layouts.tenant :title="'Azienda — '.$company->name">
    <x-ui.page>
        <x-ui.page-header
            :title="$company->name"
            :subtitle="'P.IVA: '.($company->vat ?: '—').' · Allievi: '.(int) ($company->users_count ?? 0)"
        >
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.companies.index') }}" class="link link-hover">Aziende</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">{{ $company->name }}</span>
            </x-slot:breadcrumb>
            <x-slot:actions>
                @tenantcan('learners.manage')
                    <a href="{{ route('tenant.admin.companies.learners.index', $company) }}"
                       class="btn btn-outline btn-sm inline-flex items-center gap-2">
                        <i class="ph ph-student text-base"></i>
                        Allievi
                    </a>
                    <a href="{{ route('tenant.admin.companies.learners.index', ['company' => $company, 'create' => 1]) }}"
                       class="btn btn-primary btn-sm inline-flex items-center gap-2">
                        <i class="ph ph-user-plus text-base"></i>
                        Nuovo allievo
                    </a>
                @endtenantcan
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid gap-0 border-b border-base-300 sm:grid-cols-2">
            <a href="{{ route('tenant.admin.companies.edit', $company) }}"
               class="flex items-center gap-3 border-b border-base-300 px-4 py-5 transition hover:bg-base-200/40 sm:border-b-0 sm:border-r lg:px-6">
                <i class="ph ph-pencil-simple text-lg text-base-content/70"></i>
                <div>
                    <div class="text-sm font-semibold">Modifica azienda</div>
                    <div class="mt-0.5 text-xs text-base-content/60">Dati fiscali, contatti e indirizzo.</div>
                </div>
            </a>
            <a href="{{ route('tenant.admin.companies.learners.index', $company) }}"
               class="flex items-center gap-3 border-b border-base-300 px-4 py-5 transition hover:bg-base-200/40 sm:border-b-0 lg:px-6">
                <i class="ph ph-users-three text-lg text-base-content/70"></i>
                <div>
                    <div class="text-sm font-semibold">Gestisci allievi</div>
                    <div class="mt-0.5 text-xs text-base-content/60">Crea corsisti e invia credenziali.</div>
                </div>
            </a>
        </div>

        <div class="grid gap-0 border-b border-base-300 lg:grid-cols-2">
            <div class="border-b border-base-300 px-4 py-6 lg:border-b-0 lg:border-r lg:px-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Dettagli</div>
                <dl class="mt-4 grid grid-cols-1 gap-3 text-sm">
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-base-content/60">Ragione sociale</dt>
                        <dd>{{ $company->legal_name ?: '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-base-content/60">P.IVA / VAT</dt>
                        <dd class="font-mono">{{ $company->vat ?: '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-base-content/60">Email</dt>
                        <dd>{{ $company->email ?: '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-base-content/60">Telefono</dt>
                        <dd>{{ $company->phone ?: '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-base-content/60">Referente</dt>
                        <dd>{{ $company->contact_name ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="px-4 py-6 lg:px-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Indirizzo & note</div>
                <div class="mt-4 text-sm">
                    @php
                        $addr = collect([
                            $company->address_line1,
                            $company->address_line2,
                            trim(implode(' ', array_filter([$company->postal_code, $company->city]))),
                            trim(implode(' ', array_filter([$company->province, $company->country]))),
                        ])->filter(fn ($v) => is_string($v) && trim($v) !== '')->values();
                    @endphp
                    <div class="border border-base-300 bg-base-200/50 p-4">
                        @if ($addr->isEmpty())
                            <div class="text-base-content/60">—</div>
                        @else
                            <div class="space-y-1">
                                @foreach ($addr as $line)
                                    <div>{{ $line }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="mt-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Note</div>
                        <div class="mt-2 whitespace-pre-wrap border border-base-300 bg-base-200/50 p-4">
                            {{ $company->notes ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
