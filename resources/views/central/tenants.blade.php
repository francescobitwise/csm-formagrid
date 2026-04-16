<x-layouts.central
    :title="config('app.name').' — Organizzazioni'"
    description="Elenco delle organizzazioni registrate sulla piattaforma centrale: domini, piano e accesso rapido per gli amministratori."
>
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Organizzazioni</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Elenco delle organizzazioni registrate sulla piattaforma centrale. Per entrare nell’area admin di un’organizzazione usa il link firmato (validità 5 minuti).
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('central.dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                    <i class="ph ph-chart-line-up"></i> Dashboard piattaforma
                </a>
                <a href="{{ route('central.register') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-blue px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    <i class="ph ph-plus-circle"></i> Nuova organizzazione
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('toast'))
            <div class="mb-6 rounded-xl border border-lime-500/40 bg-lime-500/10 px-4 py-3 text-sm text-lime-100">
                {{ session('toast') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
            <div class="border-b border-white/10 px-6 py-4">
                <h2 class="text-sm font-semibold text-white">Tutte le organizzazioni</h2>
                <p class="text-xs text-slate-500">Dominio principale e primo utente admin (per ordine di creazione).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-white/10 bg-slate-950/50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Organizzazione</th>
                            <th class="px-6 py-3">Dominio</th>
                            <th class="px-6 py-3">Admin</th>
                            <th class="px-6 py-3">Stato</th>
                            <th class="px-6 py-3 text-right min-w-[280px]">Piano / azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">{{ $row['company_name'] ?? $row['id'] }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $row['id'] }}</div>
                                    @if ($row['plan'])
                                        <div class="mt-1 text-xs text-slate-500">Piano: {{ $row['plan'] }}</div>
                                    @endif
                                    @if ($row['error'])
                                        <div class="mt-1 text-xs text-rose-400">{{ \Illuminate\Support\Str::limit($row['error'], 120) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    @if ($row['primary_domain'])
                                        <span class="font-mono text-xs">{{ $row['primary_domain'] }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    @if ($row['admin_email'])
                                        <span class="text-sm">{{ $row['admin_email'] }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    @if (! empty($row['health']))
                                        @php $h = $row['health']; @endphp
                                        @if ($h['level'] === 'error')
                                            <span class="inline-flex rounded-full border border-rose-500/40 bg-rose-500/15 px-2.5 py-0.5 text-xs font-semibold text-rose-200"
                                                  title="{{ $h['summary'] }}">Errore</span>
                                        @elseif ($h['level'] === 'warn')
                                            <span class="inline-flex rounded-full border border-amber-500/40 bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-100"
                                                  title="{{ $h['summary'] }}">Attenzione</span>
                                        @else
                                            <span class="inline-flex rounded-full border border-lime-500/35 bg-lime-500/10 px-2.5 py-0.5 text-xs font-semibold text-lime-100"
                                                  title="{{ $h['summary'] }}">OK</span>
                                        @endif
                                        <div class="mt-1 max-w-[14rem] text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($h['summary'], 72) }}</div>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        <form method="post"
                                              action="{{ route('central.tenants.update-plan', ['tenant' => $row['id']]) }}"
                                              class="flex flex-wrap items-center justify-end gap-2">
                                            @csrf
                                            <label class="sr-only" for="plan-{{ $row['id'] }}">Piano</label>
                                            <select id="plan-{{ $row['id'] }}" name="plan"
                                                    class="max-w-[140px] rounded-lg border border-slate-600 bg-slate-950 px-2 py-1.5 text-xs text-slate-200">
                                                @foreach (config('tenant_plans.plans', []) as $planKey => $meta)
                                                    <option value="{{ $planKey }}" @selected(($row['plan'] ?? '') === $planKey)>
                                                        {{ $meta['label'] ?? $planKey }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-white/15 bg-white/5 px-2 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/10">
                                                Salva piano
                                            </button>
                                        </form>
                                        <a href="{{ route('central.tenants.invoices', ['tenant' => $row['id']]) }}"
                                           data-no-loader
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-sky-500/50 bg-slate-950 px-3 py-1.5 text-xs font-semibold text-sky-300 shadow-sm transition hover:border-sky-400 hover:bg-slate-900 hover:text-sky-200">
                                            <i class="ph ph-invoice"></i> Fatture
                                        </a>
                                        @if (! empty($row['stripe_id']))
                                            <form method="post"
                                                  action="{{ route('central.tenants.billing-portal', ['tenant' => $row['id']]) }}"
                                                  class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-violet-500/55 bg-slate-950 px-3 py-1.5 text-xs font-semibold text-violet-300 shadow-sm transition hover:border-violet-400 hover:bg-slate-900 hover:text-violet-200">
                                                    <i class="ph ph-credit-card"></i> Portale Stripe
                                                </button>
                                            </form>
                                        @endif
                                        @if ($row['primary_domain'] && $row['admin_user_id'] && ! $row['error'])
                                            <form method="post"
                                                  action="{{ route('central.tenants.saas-login', ['tenant' => $row['id']]) }}"
                                                  class="inline keep-white-in-light">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand-navy bg-brand-blue px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-navy">
                                                    <i class="ph ph-sign-in"></i> Accedi come admin
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-600">Accesso admin non disponibile</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">Nessuna organizzazione.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.central>
