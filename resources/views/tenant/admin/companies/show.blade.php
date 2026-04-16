<x-layouts.tenant :title="'Azienda — '.$company->name">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.companies.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Aziende</a>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">{{ $company->name }}</h1>
                    <p class="admin-subtitle">
                        P.IVA: <span class="font-mono text-slate-200">{{ $company->vat ?: '—' }}</span> ·
                        Allievi: <span class="text-slate-200">{{ (int) ($company->users_count ?? 0) }}</span>
                    </p>
                </div>
                @tenantcan('learners.manage')
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('tenant.admin.companies.learners.index', $company) }}"
                           class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-2 text-xs">
                            <i class="ph ph-student text-base"></i>
                            Allievi
                        </a>
                        <a href="{{ route('tenant.admin.companies.learners.create', $company) }}"
                           class="admin-btn-primary inline-flex items-center gap-2 px-3 py-2 text-xs">
                            <i class="ph ph-user-plus text-base"></i>
                            Nuovo allievo
                        </a>
                    </div>
                @endtenantcan
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <a href="{{ route('tenant.admin.companies.edit', $company) }}"
                   class="glass-card rounded-xl border border-white/5 p-5 transition hover:bg-white/[0.03]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-pencil-simple text-lg text-slate-200"></i>
                        <div>
                            <div class="text-sm font-semibold text-white">Modifica azienda</div>
                            <div class="mt-0.5 text-xs text-slate-500">Dati fiscali, contatti e indirizzo.</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('tenant.admin.companies.learners.index', $company) }}"
                   class="glass-card rounded-xl border border-white/5 p-5 transition hover:bg-white/[0.03]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users-three text-lg text-slate-200"></i>
                        <div>
                            <div class="text-sm font-semibold text-white">Gestisci allievi</div>
                            <div class="mt-0.5 text-xs text-slate-500">Crea corsisti e invia credenziali.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="glass-card rounded-xl border border-white/5 p-6">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dettagli</div>
                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm">
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-slate-500">Ragione sociale</dt>
                            <dd class="text-slate-200">{{ $company->legal_name ?: '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-slate-500">P.IVA / VAT</dt>
                            <dd class="font-mono text-slate-200">{{ $company->vat ?: '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-slate-500">Email</dt>
                            <dd class="text-slate-200">{{ $company->email ?: '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-slate-500">Telefono</dt>
                            <dd class="text-slate-200">{{ $company->phone ?: '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-slate-500">Referente</dt>
                            <dd class="text-slate-200">{{ $company->contact_name ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="glass-card rounded-xl border border-white/5 p-6">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Indirizzo & note</div>
                    <div class="mt-4 text-sm text-slate-200">
                        @php
                            $addr = collect([
                                $company->address_line1,
                                $company->address_line2,
                                trim(implode(' ', array_filter([$company->postal_code, $company->city]))),
                                trim(implode(' ', array_filter([$company->province, $company->country]))),
                            ])->filter(fn ($v) => is_string($v) && trim($v) !== '')->values();
                        @endphp
                        <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                            @if ($addr->isEmpty())
                                <div class="text-slate-500">—</div>
                            @else
                                <div class="space-y-1">
                                    @foreach ($addr as $line)
                                        <div>{{ $line }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="mt-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Note</div>
                            <div class="mt-2 whitespace-pre-wrap rounded-lg border border-white/10 bg-white/5 p-4 text-slate-200">
                                {{ $company->notes ?: '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>

