<x-layouts.tenant :title="'Admin — '.tenant('id')">
    <x-ui.page>
        @if ($errors->has('billing'))
            <div class="px-4 pt-2 lg:px-6">
                <x-ui.alert type="warning" class="mb-4">
                    {{ $errors->first('billing') }}
                </x-ui.alert>
            </div>
        @endif

        <x-ui.page-header title="Panoramica piattaforma" subtitle="Monitora le performance della formazione.">
            <x-slot:actions>
                @tenantcan('reports.view')
                    <a href="{{ route('tenant.admin.dashboard.export') }}" class="btn btn-outline flex items-center gap-2">
                        <i class="ph ph-export"></i> Esporta CSV
                    </a>
                @endtenantcan
                @tenantcan('content.courses.manage')
                    <a href="{{ route('tenant.admin.courses.index', ['create' => 1]) }}" class="btn btn-primary">
                        <i class="ph ph-plus-circle text-lg"></i> Nuovo corso
                    </a>
                @endtenantcan
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid gap-5 border-b border-base-300 px-4 py-6 sm:grid-cols-2 xl:grid-cols-4 lg:px-6">
            <x-ui.stat
                title="Corsi pubblicati"
                :value="$stats['corsi_pubblicati']"
                :description="'Bozza: '.$stats['corsi_bozza'].' · Archiviati: '.$stats['corsi_archiviati']"
                icon="ph-books"
            />
            @tenantcan('learners.manage')
                <x-ui.stat
                    title="Allievi (learner)"
                    :value="$stats['allievi']"
                    description="Gestisci CSV e invio credenziali →"
                    icon="ph-student"
                    :href="route('tenant.admin.learners.index')"
                />
            @else
                <x-ui.stat
                    title="Allievi"
                    :value="$stats['allievi']"
                    description="Accesso gestione riservato agli amministratori."
                    icon="ph-student"
                    class="opacity-80"
                />
            @endtenantcan
            <x-ui.stat
                title="Iscrizioni completate"
                :value="$stats['iscrizioni_completate']"
                :description="'Attive: '.$stats['iscrizioni_attive'].' · Totale: '.$stats['iscrizioni_totali']"
                icon="ph-check-circle"
            />
            <x-ui.stat
                title="Certificati emessi"
                :value="$stats['certificati_emessi']"
                description="Gli allievi scaricano il PDF dal corso o da “I miei corsi” dopo il completamento."
                icon="ph-certificate"
            />
        </div>
    </x-ui.page>
</x-layouts.tenant>
