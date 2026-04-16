<x-layouts.tenant :title="'Report aziende — '.$course->title">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <a href="{{ route('tenant.admin.courses.learners', $course) }}" class="text-sm text-slate-400 hover:text-white">&larr; Report corsisti</a>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Report aziende</h1>
                    <p class="admin-subtitle">Corso: <span class="text-slate-200">{{ $course->title }}</span></p>
                    <p class="mt-2 text-sm text-slate-500">
                        Totale ore (tutte le aziende): <strong class="text-slate-300">{{ number_format(($totalSecondsAll ?? 0) / 3600, 2, ',', '.') }}</strong>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('tenant.admin.courses.companies-report.csv', $course) }}"
                       class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-2 text-xs">
                        <i class="ph ph-file-csv text-base"></i>
                        Esporta CSV
                    </a>
                </div>
            </div>

            <div class="mt-6 glass-card overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Azienda</th>
                            <th class="px-4 py-3">Corsisti</th>
                            <th class="px-4 py-3">Iscrizioni completate</th>
                            <th class="px-4 py-3 text-right">Ore totali</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($rows as $r)
                            @php
                                $name = $r->company_name ?: 'Senza azienda';
                                $seconds = (int) ($r->total_seconds ?? 0);
                            @endphp
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-white">{{ $name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ (int) ($r->learners_count ?? 0) }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ (int) ($r->completed_enrollments ?? 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-200">
                                    {{ number_format($seconds / 3600, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">Nessun dato. Il corso non ha ancora iscrizioni.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.tenant>

