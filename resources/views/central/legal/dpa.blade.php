<x-layouts.central
    :title="config('app.name').' — DPA (Responsabile del trattamento)'"
    description="Accordo sul trattamento dei dati (DPA) per clienti B2B."
>
    @php
        $c = (array) config('legal', []);
        $effective = (string) ($c['effective_date'] ?? '');
        $company = (string) ($c['company_name'] ?? config('app.name'));
        $privacyEmail = (string) ($c['privacy_email'] ?? config('mail.from.address'));
    @endphp

    <section class="mx-auto max-w-[900px] px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">DPA — Accordo sul trattamento dei dati</h1>
            <p class="mt-2 text-sm text-slate-400">
                Data di efficacia: <span class="font-mono text-slate-300">{{ $effective ?: '—' }}</span>
            </p>
        </div>

        <div class="space-y-8 text-sm leading-relaxed text-slate-200">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">Sintesi</h2>
                <p class="mt-2 text-slate-300">
                    Questo DPA disciplina il trattamento dei dati personali effettuato da <strong class="text-white">{{ $company }}</strong> come
                    <strong class="text-white">Responsabile del trattamento</strong> per conto del Cliente (Titolare) nell’uso del servizio LMS.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">1. Oggetto e durata</h2>
                <p class="mt-2 text-slate-300">
                    Il Responsabile tratta i dati per erogare il servizio (hosting applicativo, gestione accessi, reporting),
                    per la durata dell’abbonamento e per i tempi tecnici necessari a chiusura/backup secondo istruzioni del Cliente.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">2. Tipologie di dati e interessati</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li>Interessati: utenti staff e learner del Cliente.</li>
                    <li>Dati: anagrafica (nome, email), credenziali, progressi/completamenti, log tecnici di sicurezza.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">3. Istruzioni del Cliente</h2>
                <p class="mt-2 text-slate-300">
                    Il trattamento avviene secondo le istruzioni documentate del Cliente e le impostazioni operative del servizio.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">4. Misure di sicurezza</h2>
                <p class="mt-2 text-slate-300">
                    Segregazione tenant, controllo accessi, logging e misure tecniche/organizzative adeguate al rischio.
                    Dettagli e sub‑responsabili sono indicati nella pagina <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.subprocessors') }}">Sub‑responsabili</a>.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">5. Sub‑responsabili</h2>
                <p class="mt-2 text-slate-300">
                    Il Responsabile può coinvolgere sub‑responsabili per erogare il servizio (es. pagamenti, hosting, email),
                    mantenendo un elenco aggiornato. Vedi <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="{{ route('central.legal.subprocessors') }}">Sub‑responsabili</a>.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">6. Assistenza e richieste degli interessati</h2>
                <p class="mt-2 text-slate-300">
                    Il Responsabile assiste il Cliente, nei limiti ragionevoli, per richieste di accesso/cancellazione/esportazione dei dati
                    e per obblighi di notifica. Contatto privacy: <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>.
                </p>
            </div>
        </div>
    </section>
</x-layouts.central>

