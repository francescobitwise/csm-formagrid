@php
    /** @var bool $hasStripeCustomer */
    /** @var \Illuminate\Support\Collection $rows */
    $statusLabels = [
        'paid' => 'Pagata',
        'open' => 'Aperta',
        'draft' => 'Bozza',
        'uncollectible' => 'Inesigibile',
        'void' => 'Annullata',
    ];
@endphp
<x-layouts.tenant :title="'Fatture — '.tenant('id')">
    <div class="admin-page-wrap">
        <div class="admin-hero flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="admin-title">Fatture e abbonamento</h1>
                <p class="admin-subtitle">Documenti emessi da Stripe per la tua organizzazione.</p>
            </div>
            @if ($hasStripeCustomer)
                <form method="post" action="{{ route('tenant.admin.billing.portal') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="admin-btn-secondary inline-flex items-center gap-1 px-3 py-1.5 text-xs">
                        <i class="ph ph-credit-card"></i> Gestisci abbonamento
                    </button>
                </form>
            @endif
        </div>

        @if ($errors->has('billing'))
            <div class="mb-6 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first('billing') }}
            </div>
        @endif

        @if (! $hasStripeCustomer)
            <div class="glass-card rounded-xl border border-white/10 p-8 text-center text-slate-400">
                Non risulta ancora un account di fatturazione collegato. Dopo la registrazione con pagamento le fatture saranno disponibili qui.
                <p class="mt-4 text-sm text-slate-500">Per metodo di pagamento e rinnovi puoi usare il portale dalla piattaforma centrale, se il tuo fornitore te lo ha abilitato.</p>
            </div>
        @elseif ($rows->isEmpty())
            <div class="glass-card rounded-xl border border-white/10 p-8 text-center text-slate-400">
                Nessuna fattura presente al momento.
            </div>
        @else
            <div class="glass-card overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-white/10 bg-slate-950/40 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Numero</th>
                                <th class="px-5 py-3">Stato</th>
                                <th class="px-5 py-3 text-right">Totale</th>
                                <th class="px-5 py-3 text-right">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-white/[0.03]">
                                    <td class="px-5 py-4 text-slate-200">
                                        {{ $row['date']->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-slate-400">
                                        {{ $row['number'] ?: '—' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @php $st = $row['status']; @endphp
                                        <span class="inline-flex rounded-lg border px-2 py-0.5 text-xs font-medium
                                            {{ $row['paid'] ? 'border-lime-500/40 bg-lime-500/10 text-lime-100' : 'border-amber-500/35 bg-amber-500/10 text-amber-100' }}">
                                            {{ $statusLabels[$st] ?? $st }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-medium text-white">
                                        {{ $row['total'] }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('tenant.admin.billing.invoices.pdf', ['invoice' => $row['id']]) }}"
                                           data-no-loader
                                           class="admin-btn-secondary inline-flex items-center gap-1 px-3 py-1.5 text-xs">
                                            <i class="ph ph-download-simple"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.tenant>
