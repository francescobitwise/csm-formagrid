<x-layouts.central
    :title="config('app.name').' — Registra organizzazione'"
    description="Registra la tua organizzazione (azienda o ente), scegli il piano e attiva lo spazio dedicato. Pagamento sicuro con Stripe."
>
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8">
            <div class="mb-8 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800/50">
                    <i class="ph ph-buildings text-xl text-brand-amber"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white">Registra la tua organizzazione</h1>
                    <p class="text-sm text-slate-400">Crea il tuo spazio dedicato in pochi minuti. Scegli il piano e completa il pagamento con Stripe.</p>
                </div>
            </div>

            <form class="space-y-5" method="post" action="{{ route('central.register.store') }}">
                @csrf

                <div>
                    <label for="company_name" class="form-label">Nome organizzazione</label>
                    <input id="company_name" name="company_name" value="{{ old('company_name') }}" class="form-input" placeholder="es. Acme Corp / Ente Formativo XY" required>
                    @error('company_name') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="tenant_id" class="form-label">Sottodominio</label>
                    <div class="flex">
                        <input id="tenant_id" name="tenant_id" value="{{ old('tenant_id') }}" class="form-input rounded-r-none border-r-0 focus:z-10" placeholder="acmecorp">
                        <span class="inline-flex items-center whitespace-nowrap rounded-r-xl border border-slate-700/80 bg-slate-800/50 px-4 text-sm text-slate-400">
                            .{{ config('app.central_domain') }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Suggerimento automatico dal nome azienda (puoi modificarlo liberamente).</p>
                    @error('tenant_id') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="plan" class="form-label">Piano in abbonamento</label>
                    <div class="relative">
                        <select id="plan" name="plan" class="form-input appearance-none pr-10" required>
                            @php
                                $planList = config('tenant_plans.plans', []);
                                $defaultPlan = (string) config('tenant_plans.default', 'pro');
                                $checkoutPlans = collect($planList)
                                    ->reject(fn ($row) => (bool) ($row['contact_only'] ?? false))
                                    ->all();
                            @endphp
                            @foreach ($checkoutPlans as $value => $meta)
                                @php
                                    $label = $meta['label'] ?? ucfirst($value);
                                    $pm = (int) ($meta['price_monthly_eur'] ?? 0);
                                    $py = (int) ($meta['price_yearly_eur'] ?? 0);
                                @endphp
                                <option value="{{ $value }}" @selected(old('plan', $defaultPlan) === $value)>
                                    {{ $label }}
                                    @if ($pm > 0) — {{ $pm }} €/mese @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <i class="ph ph-caret-down"></i>
                        </div>
                    </div>
                    <div id="plan_details" class="mt-2 rounded-lg border border-slate-700/50 bg-slate-900/40 p-3 text-sm text-slate-300">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Dettagli piano</div>
                                <div class="mt-1 font-semibold text-white"><span data-plan-label>—</span></div>
                            </div>
                            <div class="text-right font-mono text-xs text-slate-400">
                                <div><span data-plan-price-monthly>—</span></div>
                                <div><span data-plan-price-yearly>—</span></div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-400">
                            <div><span class="font-semibold text-slate-300" data-plan-learners>—</span> allievi</div>
                            <div><span class="font-semibold text-slate-300" data-plan-courses>—</span> corsi</div>
                            <div><span class="font-semibold text-slate-300" data-plan-storage>—</span> GB storage</div>
                            <div><span class="font-semibold text-slate-300" data-plan-domain>—</span></div>
                        </div>
                    </div>
                    <div class="mt-2 rounded-lg border border-slate-700/50 bg-slate-900/30 p-3 text-xs text-slate-400">
                        <span class="font-semibold text-slate-200">Enterprise</span>: piano su misura.
                        <a class="font-semibold text-brand-amber hover:text-brand-amber/90"
                           href="{{ 'https://mail.google.com/mail/?view=cm&fs=1&to='.rawurlencode((string) config('legal.email', config('mail.from.address'))).'&su='.rawurlencode('Richiesta piano Enterprise — '.config('app.name', 'FormaGrid')) }}">
                            Contattaci
                        </a>
                        per valutare requisiti e pricing.
                    </div>
                    @error('plan') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="billing_interval" class="form-label">Fatturazione</label>
                    <select id="billing_interval" name="billing_interval" class="form-input" required>
                        <option value="monthly" @selected(old('billing_interval', 'monthly') === 'monthly')>Mensile</option>
                        <option value="yearly" @selected(old('billing_interval') === 'yearly')>Annuale</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Verrai reindirizzato al pagamento sicuro con Stripe.</p>
                </div>

                <div>
                    <label for="billing_email" class="form-label">Email di registrazione</label>
                    <input id="billing_email" type="email" name="billing_email" value="{{ old('billing_email') }}" class="form-input" placeholder="tu@azienda.it" autocomplete="email" required>
                    <p class="mt-1 text-xs text-slate-500">Stesso indirizzo per ricevute Stripe/fatturazione e per l’email con le credenziali del primo amministratore dello spazio.</p>
                    @error('billing_email') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div class="rounded-xl border border-slate-700/50 bg-slate-900/40 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Riepilogo</div>
                    <div class="mt-3 space-y-2 text-sm text-slate-300">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-slate-400">Piano selezionato</div>
                            <div class="font-semibold text-white"><span data-summary-plan>—</span> · <span data-summary-price>—</span></div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-slate-400">Sottodominio</div>
                            <div class="font-mono text-xs text-brand-blue"><span data-summary-domain>—</span></div>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <div class="mb-3 text-center text-xs text-slate-500">
                        <span class="font-semibold text-slate-300">Pagamento sicuro</span> · Disdici quando vuoi · Nessun vincolo
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-brand-blue px-4 py-3 font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        Procedi al pagamento →
                    </button>
                    <div class="mt-3 text-center text-xs text-slate-500">
                        Prova gratuita <span class="font-semibold text-slate-300">14 giorni</span> sul piano <span class="font-semibold text-slate-300">Basic</span>.
                    </div>
                    <div class="mt-4 text-center text-sm text-slate-400">
                        Hai già un account?
                        <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.login') }}">Accedi →</a>
                    </div>
                </div>

                @if (session('created_domain'))
                    <div class="mt-2 rounded-lg border border-slate-700/50 bg-slate-900/50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-brand-amber">Creato</div>
                        <div class="mt-2 font-mono text-sm text-brand-blue">{{ session('created_domain') }}</div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script type="application/json" id="checkout_plans_json">@json($checkoutPlans)</script>
    <script type="application/json" id="central_domain_json">@json((string) config('app.central_domain'))</script>

    <script>
        (function () {
            const company = document.getElementById('company_name');
            const tenantId = document.getElementById('tenant_id');
            const plan = document.getElementById('plan');
            const billingInterval = document.getElementById('billing_interval');
            if (!company || !tenantId) return;

            let tenantTouched = (tenantId.value || '').trim().length > 0;

            const slugify = (value) => {
                return (value || '')
                    .toString()
                    .normalize('NFKD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .slice(0, 60);
            };

            const suggest = () => {
                if (tenantTouched) return;
                const s = slugify(company.value);
                if (s.length >= 3) tenantId.value = s;
            };

            tenantId.addEventListener('input', () => {
                tenantTouched = (tenantId.value || '').trim().length > 0;
            });

            const plans = JSON.parse(document.getElementById('checkout_plans_json')?.textContent || '{}');
            const centralDomain = JSON.parse(document.getElementById('central_domain_json')?.textContent || '""') || '';

            const fmtMoney = (n) => {
                const v = Number(n || 0);
                if (!Number.isFinite(v) || v <= 0) return '—';
                return `${v} €`;
            };

            const textOrUnlimited = (n) => {
                const v = Number(n);
                return v === -1 ? 'Illimitati' : (Number.isFinite(v) ? String(v) : '—');
            };

            const updatePlanDetails = () => {
                if (!plan) return;
                const key = plan.value;
                const meta = plans?.[key] || {};

                const label = meta.label || (key ? key.charAt(0).toUpperCase() + key.slice(1) : '—');
                const pm = meta.price_monthly_eur ?? 0;
                const py = meta.price_yearly_eur ?? 0;
                const courses = meta.courses ?? '—';
                const learners = meta.learners_max ?? '—';
                const storage = meta.storage_gb ?? '—';
                const customDomain = !!meta.custom_domain;

                const set = (sel, value) => {
                    const el = document.querySelector(sel);
                    if (el) el.textContent = value;
                };

                set('[data-plan-label]', label);
                set('[data-plan-price-monthly]', pm ? `${fmtMoney(pm)}/mese` : '—');
                set('[data-plan-price-yearly]', py ? `${fmtMoney(py)}/anno` : '—');
                set('[data-plan-learners]', textOrUnlimited(learners));
                set('[data-plan-courses]', textOrUnlimited(courses));
                set('[data-plan-storage]', Number.isFinite(Number(storage)) ? String(storage) : '—');
                set('[data-plan-domain]', customDomain ? 'Dominio proprio' : 'Solo sottodominio');
            };

            const updateSummary = () => {
                const key = plan ? plan.value : '';
                const meta = plans?.[key] || {};
                const label = meta.label || (key ? key.charAt(0).toUpperCase() + key.slice(1) : '—');

                const interval = billingInterval ? billingInterval.value : 'monthly';
                const price = interval === 'yearly' ? meta.price_yearly_eur : meta.price_monthly_eur;
                const priceText = price ? `${fmtMoney(price)}/${interval === 'yearly' ? 'anno' : 'mese'}` : '—';

                const host = (tenantId.value || '').trim();
                const fqdn = host ? `${host}.${centralDomain}` : `—.${centralDomain}`;

                const set = (sel, value) => {
                    const el = document.querySelector(sel);
                    if (el) el.textContent = value;
                };

                set('[data-summary-plan]', label);
                set('[data-summary-price]', priceText);
                set('[data-summary-domain]', fqdn);
            };

            if (plan) plan.addEventListener('change', () => { updatePlanDetails(); updateSummary(); });
            if (billingInterval) billingInterval.addEventListener('change', updateSummary);
            tenantId.addEventListener('input', updateSummary);

            company.addEventListener('input', () => {
                suggest();
                updateSummary();
            });

            suggest();
            updatePlanDetails();
            updateSummary();
        })();
    </script>
</x-layouts.central>

