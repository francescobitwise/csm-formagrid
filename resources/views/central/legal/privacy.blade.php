<x-layouts.central
    :title="config('app.name').' — Privacy Policy'"
    description="Informativa privacy per il servizio e-learning SaaS."
>
    @php
        $c = (array) config('legal', []);
        $effective = (string) ($c['effective_date'] ?? '');
        $company = (string) ($c['company_name'] ?? config('app.name'));
        $address = (string) ($c['address'] ?? '');
        $vat = (string) ($c['vat'] ?? '');
        $email = (string) ($c['email'] ?? config('mail.from.address'));
        $privacyEmail = (string) ($c['privacy_email'] ?? $email);
    @endphp

    <section class="mx-auto max-w-[900px] px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">Privacy Policy</h1>
            <p class="mt-2 text-sm text-slate-400">
                Data di efficacia: <span class="font-mono text-slate-300">{{ $effective ?: '—' }}</span>
            </p>
        </div>

        <div class="space-y-8 text-sm leading-relaxed text-slate-200">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">1. Titolare del trattamento (sito e marketing)</h2>
                <p class="mt-2 text-slate-300">
                    Il Titolare del trattamento dei dati raccolti sul sito e per finalità commerciali è
                    <strong class="text-white">{{ $company }}</strong>
                    @if ($address !== '') — {{ $address }} @endif
                    @if ($vat !== '') — P. IVA {{ $vat }} @endif
                    .
                </p>
                <p class="mt-2 text-slate-300">
                    Contatti: <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $email }}">{{ $email }}</a>
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">2. Ruoli privacy nel servizio (B2B)</h2>
                <p class="mt-2 text-slate-300">
                    Quando un’organizzazione cliente crea il proprio spazio e-learning e inserisce utenti/corsisti, di norma:
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li>l’organizzazione cliente è <strong class="text-white">Titolare</strong> dei dati degli utenti;</li>
                    <li>{{ $company }} opera come <strong class="text-white">Responsabile del trattamento</strong> per l’erogazione del servizio.</li>
                </ul>
                <p class="mt-3 text-slate-300">
                    I dettagli sono disciplinati nel <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.dpa') }}">DPA</a>.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">3. Dati trattati</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li><strong class="text-white">Dati di registrazione</strong>: email, nome organizzazione, sottodominio.</li>
                    <li><strong class="text-white">Dati di utilizzo LMS</strong>: progressi, completamenti, log di accesso e attività.</li>
                    <li><strong class="text-white">Dati di fatturazione</strong>: identificativi e documenti emessi dal provider pagamenti (Stripe).</li>
                    <li><strong class="text-white">Dati tecnici</strong>: indirizzo IP, user agent, log applicativi e di sicurezza.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">4. Finalità e basi giuridiche (sito e marketing)</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li><strong class="text-white">Rispondere a richieste commerciali</strong> e gestire contatti (art. 6(1)(b) GDPR).</li>
                    <li><strong class="text-white">Adempimenti</strong> amministrativi e fiscali (art. 6(1)(c) GDPR).</li>
                    <li><strong class="text-white">Analytics</strong> per misurare performance del sito (art. 6(1)(a) GDPR: consenso, se non tecnici).</li>
                    <li><strong class="text-white">Sicurezza</strong> e prevenzione abusi (art. 6(1)(f) GDPR: legittimo interesse).</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">5. Cookie e strumenti di tracciamento</h2>
                <p class="mt-2 text-slate-300">
                    Usiamo cookie tecnici necessari e, se attivati, strumenti di analytics previo consenso.
                    Dettagli nella <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.cookies') }}">Cookie Policy</a>.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">6. Conservazione</h2>
                <p class="mt-2 text-slate-300">
                    Conserviamo i dati per il tempo necessario alle finalità indicate e agli obblighi di legge; per il servizio LMS,
                    la conservazione è regolata dal contratto/DPA con il cliente.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">7. Diritti degli interessati e contatti privacy</h2>
                <p class="mt-2 text-slate-300">
                    Per i dati trattati come Titolare (sito/marketing) puoi esercitare i diritti GDPR scrivendo a
                    <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>.
                    Per i dati trattati nello spazio e-learning, la richiesta va rivolta al Titolare (l’organizzazione cliente).
                </p>
            </div>
        </div>
    </section>
</x-layouts.central>

