<x-layouts.central
    :title="config('app.name').' — Sub-responsabili'"
    description="Elenco sub-responsabili (fornitori) usati per erogare il servizio."
>
    @php
        $c = (array) config('legal', []);
        $effective = (string) ($c['effective_date'] ?? '');
        $company = (string) ($c['company_name'] ?? config('app.name'));
        $privacyEmail = (string) ($c['privacy_email'] ?? config('mail.from.address'));
        $subprocessors = (array) ($c['subprocessors'] ?? []);
    @endphp

    <section class="mx-auto max-w-[900px] px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">Sub‑responsabili</h1>
            <p class="mt-2 text-sm text-slate-400">
                Data di efficacia: <span class="font-mono text-slate-300">{{ $effective ?: '—' }}</span>
            </p>
        </div>

        <div class="space-y-6 text-sm leading-relaxed text-slate-200">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <p class="text-slate-300">
                    Elenco dei fornitori principali che possono trattare dati per conto di <strong class="text-white">{{ $company }}</strong>
                    nell’erogazione del servizio.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/40 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Fornitore</th>
                            <th class="px-6 py-3">Servizio</th>
                            <th class="px-6 py-3">Dati</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        @forelse ($subprocessors as $row)
                            @php
                                $vendor = (string) ($row['vendor'] ?? '');
                                $service = (string) ($row['service'] ?? '');
                                $data = (string) ($row['data'] ?? '');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 font-semibold text-white">{{ $vendor }}</td>
                                <td class="px-6 py-4">{{ $service }}</td>
                                <td class="px-6 py-4">{{ $data }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-4 text-slate-300" colspan="3">
                                    Elenco non disponibile.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <p class="text-slate-300">
                    Contatto privacy: <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.central>

