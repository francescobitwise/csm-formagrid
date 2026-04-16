@php
    /** @var callable $formatBytes */
    $t = $stats['totals'];
@endphp
<x-layouts.central
    :title="config('app.name').' — Dashboard piattaforma'"
    description="Panoramica della piattaforma centrale: organizzazioni, utilizzo e statistiche per gli amministratori."
>
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Dashboard piattaforma</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Riepilogo piattaforma: organizzazioni registrate, corsi, partecipanti, media e iscrizioni.
                    @if ($stats['generated_at'])
                        <span class="text-slate-500">· Aggiornato {{ \Carbon\Carbon::parse($stats['generated_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('central.dashboard', ['refresh' => 1]) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                    <i class="ph ph-arrows-clockwise"></i> Ricalcola
                </a>
                <a href="{{ route('central.register') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-blue px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    <i class="ph ph-plus-circle"></i> Nuova organizzazione
                </a>
            </div>
        </div>

        @if ($t['storage_partial'])
            <div class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                <strong>Storage:</strong> stima parziale per almeno un’organizzazione (disco non locale tipo S3, troppi file da misurare, o errore DB).
                I valori mostrati sono solo per le organizzazioni misurate con successo.
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Organizzazioni</div>
                <div class="mt-2 text-3xl font-bold tabular-nums text-white">{{ number_format($t['tenants']) }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Corsi (totale)</div>
                <div class="mt-2 text-3xl font-bold tabular-nums text-white">{{ number_format($t['courses']) }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Allievi (learner)</div>
                <div class="mt-2 text-3xl font-bold tabular-nums text-white">{{ number_format($t['learners']) }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Storage media (stima)</div>
                <div class="mt-2 text-3xl font-bold tabular-nums text-white">
                    @if (! $t['storage_partial'])
                        {{ $formatBytes($t['storage_bytes']) }}
                    @elseif ($t['storage_bytes'] > 0)
                        ≥ {{ $formatBytes($t['storage_bytes']) }}
                    @else
                        —
                    @endif
                </div>
                <div class="mt-1 text-[11px] text-slate-500">Disco <code class="text-slate-400">{{ config('media.disk') }}</code>: somma file per organizzazione (locale = radice disco; S3 = prefisso <code class="text-slate-400">tenants/&lt;id&gt;/</code>). Oltre {{ number_format(\App\Services\MediaStorageUsageService::MAX_FILES_TO_MEASURE) }} file la colonna mostra “—”.</div>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Moduli</div>
                <div class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($t['modules']) }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Lezioni</div>
                <div class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($t['lessons']) }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Staff (totale)</div>
                <div class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($t['staff']) }}</div>
                <div class="mt-1 text-[11px] text-slate-500">Admin + istruttori</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Iscrizioni</div>
                <div class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($t['enrollments']) }}</div>
            </div>
        </div>

        <div class="mt-10 overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
            <div class="border-b border-white/10 px-6 py-4">
                <h2 class="text-sm font-semibold text-white">Dettaglio per organizzazione</h2>
                <p class="text-xs text-slate-500">Dominio principale, piano e conteggi dal database di ciascuna organizzazione.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Organizzazione</th>
                            <th class="px-6 py-3">Dominio</th>
                            <th class="px-6 py-3">Piano</th>
                            <th class="px-6 py-3 text-right">Corsi</th>
                            <th class="px-6 py-3 text-right">Allievi</th>
                            <th class="px-6 py-3 text-right">Staff</th>
                            <th class="px-6 py-3 text-right">Moduli</th>
                            <th class="px-6 py-3 text-right">Lezioni</th>
                            <th class="px-6 py-3 text-right">Iscrizioni</th>
                            <th class="px-6 py-3 text-right">Storage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($stats['tenants'] as $row)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">{{ $row['company_name'] ?? $row['id'] }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $row['id'] }}</div>
                                    @if ($row['error'])
                                        <div class="mt-1 text-xs text-rose-400">{{ \Illuminate\Support\Str::limit($row['error'], 80) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    @if ($row['primary_domain'])
                                        <span class="font-mono text-xs">{{ $row['primary_domain'] }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $row['plan'] ?? '—' }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['courses']) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['learners']) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['staff']) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['modules']) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['lessons']) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-200">{{ number_format($row['enrollments']) }}</td>
                                <td class="px-6 py-4 text-right text-xs tabular-nums text-slate-300">
                                    @if ($row['storage_known'] && $row['storage_bytes'] !== null)
                                        {{ $formatBytes($row['storage_bytes']) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">Nessuna organizzazione ancora. Creane una dalla registrazione.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.central>
