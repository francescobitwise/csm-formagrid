@php
    /** @var \App\Models\Landlord\Tenant $tenant */
    /** @var bool $hasStripeCustomer */
    /** @var \Illuminate\Support\Collection $rows */
    $statusLabels = [
        'paid' => 'Pagata',
        'open' => 'Aperta',
        'draft' => 'Bozza',
        'uncollectible' => 'Inesigibile',
        'void' => 'Annullata',
    ];
    $orgLabel = $tenant->company_name ?? $tenant->stripeName() ?? $tenant->getKey();
@endphp
<x-layouts.central
    :title="config('app.name').' — Fatture · '.$orgLabel"
    description="Fatture Stripe dell’organizzazione: scarica i PDF generati da Laravel Cashier."
>
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('central.tenants.index') }}"
                   class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-400 transition hover:text-white">
                    <i class="ph ph-arrow-left"></i> Organizzazioni
                </a>
                <h1 class="text-3xl font-bold tracking-tight text-white">Fatture</h1>
                <p class="mt-1 text-sm text-slate-400">
                    <span class="font-mono text-slate-300">{{ $tenant->getKey() }}</span>
                    @if ($tenant->domains->first()?->domain)
                        · <span class="font-mono">{{ $tenant->domains->first()->domain }}</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($hasStripeCustomer)
                    <form method="post" action="{{ route('central.tenants.billing-portal', ['tenant' => $tenant]) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-violet-400/40 bg-violet-400/10 px-4 py-2 text-sm font-semibold text-violet-100 transition hover:bg-violet-400/20">
                            <i class="ph ph-credit-card"></i> Portale Stripe
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if (! $hasStripeCustomer)
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 px-6 py-12 text-center text-slate-400">
                Questa organizzazione non ha ancora un cliente Stripe collegato. Dopo un checkout o un abbonamento attivo le fatture appariranno qui.
            </div>
        @elseif ($rows->isEmpty())
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 px-6 py-12 text-center text-slate-400">
                Nessuna fattura trovata su Stripe per questo cliente.
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Data</th>
                                <th class="px-6 py-3">Numero</th>
                                <th class="px-6 py-3">Stato</th>
                                <th class="px-6 py-3 text-right">Totale</th>
                                <th class="px-6 py-3 text-right">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-6 py-4 text-slate-200">
                                        {{ $row['date']->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                        {{ $row['number'] ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php $st = $row['status']; @endphp
                                        <span class="inline-flex rounded-lg border px-2 py-0.5 text-xs font-medium
                                            {{ $row['paid'] ? 'border-lime-500/40 bg-lime-500/10 text-lime-100' : 'border-amber-500/35 bg-amber-500/10 text-amber-100' }}">
                                            {{ $statusLabels[$st] ?? $st }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-white">
                                        {{ $row['total'] }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('central.tenants.invoices.pdf', ['tenant' => $tenant, 'invoice' => $row['id']]) }}"
                                           data-no-loader
                                           class="inline-flex items-center gap-1 rounded-lg border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/10">
                                            <i class="ph ph-download-simple"></i> Scarica
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
</x-layouts.central>
