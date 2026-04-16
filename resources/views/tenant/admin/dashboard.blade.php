<x-layouts.tenant :title="'Admin — '.tenant('id')">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="admin-page-wrap">
            @if ($errors->has('billing'))
                <div class="mb-6 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                    {{ $errors->first('billing') }}
                </div>
            @endif
            <div class="admin-hero flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Panoramica piattaforma</h1>
                    <p class="admin-subtitle">Monitora le performance della formazione.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @tenantcan('reports.view')
                        <a href="{{ route('tenant.admin.dashboard.export') }}" class="admin-btn-secondary flex items-center gap-2">
                            <i class="ph ph-export"></i> Esporta CSV
                        </a>
                    @endtenantcan
                    @tenantcan('content.courses.manage')
                        <a href="{{ route('tenant.admin.courses.create') }}" class="admin-btn-primary">
                            <i class="ph ph-plus-circle text-lg"></i> Nuovo corso
                        </a>
                    @endtenantcan
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="glass-card relative overflow-hidden rounded-xl border border-white/5 p-5">
                    <div class="absolute right-0 top-0 p-4 opacity-10"><i class="ph ph-books text-6xl"></i></div>
                    <div class="text-sm font-medium text-slate-400">Corsi pubblicati</div>
                    <div class="mt-2 text-3xl font-bold text-white">{{ $stats['corsi_pubblicati'] }}</div>
                    <div class="mt-2 text-xs text-slate-500">Bozza: {{ $stats['corsi_bozza'] }} · Archiviati: {{ $stats['corsi_archiviati'] }}</div>
                </div>
                @tenantcan('learners.manage')
                    <a href="{{ route('tenant.admin.learners.index') }}" class="glass-card relative block overflow-hidden rounded-xl border border-white/5 p-5 transition hover:border-brand-blue/25">
                        <div class="absolute right-0 top-0 p-4 opacity-10"><i class="ph ph-student text-6xl"></i></div>
                        <div class="text-sm font-medium text-slate-400">Allievi (learner)</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['allievi'] }}</div>
                        <div class="mt-2 text-xs text-brand-blue/90">Gestisci CSV e invio credenziali →</div>
                    </a>
                @else
                    <div class="glass-card relative overflow-hidden rounded-xl border border-white/5 p-5 opacity-80">
                        <div class="absolute right-0 top-0 p-4 opacity-10"><i class="ph ph-student text-6xl"></i></div>
                        <div class="text-sm font-medium text-slate-400">Allievi</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['allievi'] }}</div>
                        <div class="mt-2 text-xs text-slate-500">Accesso gestione riservato agli amministratori.</div>
                    </div>
                @endtenantcan
                <div class="glass-card relative overflow-hidden rounded-xl border border-white/5 p-5">
                    <div class="absolute right-0 top-0 p-4 opacity-10"><i class="ph ph-check-circle text-6xl"></i></div>
                    <div class="text-sm font-medium text-slate-400">Iscrizioni completate</div>
                    <div class="mt-2 text-3xl font-bold text-white">{{ $stats['iscrizioni_completate'] }}</div>
                    <div class="mt-2 text-xs text-slate-500">Attive: {{ $stats['iscrizioni_attive'] }} · Totale: {{ $stats['iscrizioni_totali'] }}</div>
                </div>
                <div class="glass-card relative overflow-hidden rounded-xl border border-white/5 p-5">
                    <div class="absolute right-0 top-0 p-4 opacity-10"><i class="ph ph-certificate text-6xl"></i></div>
                    <div class="text-sm font-medium text-slate-400">Certificati emessi</div>
                    <div class="mt-2 text-3xl font-bold text-white">{{ $stats['certificati_emessi'] }}</div>
                    <div class="mt-2 text-xs text-slate-500">Gli allievi scaricano il PDF dal corso o da “I miei corsi” dopo il completamento.</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
