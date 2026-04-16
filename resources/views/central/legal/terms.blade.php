<x-layouts.central
    :title="config('app.name').' — Termini di Servizio'"
    description="Termini e condizioni del servizio e-learning SaaS (B2B)."
>
    @php
        $c = (array) config('legal', []);
        $effective = (string) ($c['effective_date'] ?? '');
        $company = (string) ($c['company_name'] ?? config('app.name'));
        $email = (string) ($c['email'] ?? config('mail.from.address'));
    @endphp

    <section class="mx-auto max-w-[900px] px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">Termini di Servizio (B2B)</h1>
            <p class="mt-2 text-sm text-slate-400">
                Data di efficacia: <span class="font-mono text-slate-300">{{ $effective ?: '—' }}</span>
            </p>
        </div>

        <div class="space-y-8 text-sm leading-relaxed text-slate-200">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">1. Oggetto</h2>
                <p class="mt-2 text-slate-300">
                    {{ $company }} fornisce un servizio SaaS per la gestione di corsi e-learning (LMS) dedicato a organizzazioni (B2B),
                    con spazi separati per ciascun cliente (tenant).
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">2. Account e responsabilità del Cliente</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li>Il Cliente è responsabile degli accessi del proprio personale/corsisti e dei contenuti caricati.</li>
                    <li>È vietato usare il servizio per attività illecite o che violino diritti di terzi.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">3. Abbonamento, prova e fatturazione</h2>
                <p class="mt-2 text-slate-300">
                    Il servizio è offerto in abbonamento secondo i piani disponibili. Eventuali periodi di prova (trial) sono indicati in fase di registrazione.
                    Pagamenti e fatture sono gestiti tramite provider terzo (Stripe).
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">4. Disponibilità e supporto</h2>
                <p class="mt-2 text-slate-300">
                    Il servizio è erogato in modalità “as is”. Eventuali livelli di servizio (SLA) e canali di supporto possono essere definiti in offerta o accordi separati.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">5. Dati e privacy</h2>
                <p class="mt-2 text-slate-300">
                    Il trattamento dei dati personali nel servizio è regolato dal <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.dpa') }}">DPA</a>
                    e dall’<a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.privacy') }}">Informativa Privacy</a>.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">6. Recesso e cessazione</h2>
                <p class="mt-2 text-slate-300">
                    Il Cliente può annullare l’abbonamento secondo le modalità previste nel portale di fatturazione.
                    Alla cessazione, l’accesso può essere sospeso e i dati trattati secondo quanto previsto dal DPA e dagli accordi con il Cliente.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">7. Contatti</h2>
                <p class="mt-2 text-slate-300">
                    Per informazioni commerciali o contrattuali: <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $email }}">{{ $email }}</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.central>

