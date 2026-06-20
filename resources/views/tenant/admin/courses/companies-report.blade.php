<x-layouts.tenant :title="'Report aziende — '.$course->title">
    <x-ui.page>
        <a href="{{ route('tenant.admin.courses.learners', $course) }}" class="link link-hover text-sm text-base-content/70">&larr; Report corsisti</a>

        <x-ui.page-header title="Report aziende" class="mt-4">
            <x-slot:subtitle>
                <p>Corso: {{ $course->title }}</p>
                <p class="mt-2 text-sm text-base-content/60">
                    Totale ore (tutte le aziende): <strong>{{ number_format(($totalSecondsAll ?? 0) / 3600, 2, ',', '.') }}</strong>
                </p>
            </x-slot:subtitle>
            <x-slot:actions>
                <a href="{{ route('tenant.admin.courses.companies-report.csv', $course) }}"
                   class="btn btn-outline btn-sm inline-flex items-center gap-2">
                    <i class="ph ph-file-csv text-base"></i>
                    Esporta CSV
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th>Azienda</th>
                            <th>Corsisti</th>
                            <th>Iscrizioni completate</th>
                            <th class="text-right">Ore totali</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php
                                $name = $r->company_name ?: 'Senza azienda';
                                $seconds = (int) ($r->total_seconds ?? 0);
                            @endphp
                            <tr>
                                <td class="font-medium">{{ $name }}</td>
                                <td class="text-base-content/70">{{ (int) ($r->learners_count ?? 0) }}</td>
                                <td class="text-base-content/70">{{ (int) ($r->completed_enrollments ?? 0) }}</td>
                                <td class="text-right font-semibold">
                                    {{ number_format($seconds / 3600, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-base-content/60">Nessun dato. Il corso non ha ancora iscrizioni.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
