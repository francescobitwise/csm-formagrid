<x-layouts.central
    :title="config('app.name').' — Cookie Policy'"
    description="Informativa cookie e preferenze di consenso."
>
    @php
        $c = (array) config('legal', []);
        $effective = (string) ($c['effective_date'] ?? '');
        $company = (string) ($c['company_name'] ?? config('app.name'));
        $privacyEmail = (string) ($c['privacy_email'] ?? config('mail.from.address'));
        $ga4 = (string) (config('analytics.ga4_measurement_id') ?? '');
    @endphp

    <section class="mx-auto max-w-[900px] px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">Cookie Policy</h1>
            <p class="mt-2 text-sm text-slate-400">
                Data di efficacia: <span class="font-mono text-slate-300">{{ $effective ?: '—' }}</span>
            </p>
        </div>

        <div class="space-y-8 text-sm leading-relaxed text-slate-200">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">1. Cosa sono i cookie</h2>
                <p class="mt-2 text-slate-300">
                    I cookie sono piccoli file di testo che il sito salva sul dispositivo per garantire funzioni essenziali e, se autorizzato,
                    per misurare l’uso del sito.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">2. Cookie tecnici (sempre attivi)</h2>
                <p class="mt-2 text-slate-300">
                    Necessari per la sicurezza e per il funzionamento (es. sessione, CSRF, preferenze).
                    Non richiedono consenso.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">3. Analytics (solo con consenso)</h2>
                <p class="mt-2 text-slate-300">
                    Se acconsenti, possiamo attivare strumenti di analytics per capire come viene usato il sito e migliorarlo.
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-slate-300">
                    <li>Provider: Google Analytics 4</li>
                    <li>Identificativo configurazione: <span class="font-mono text-xs text-slate-400">{{ $ga4 !== '' ? $ga4 : 'non configurato' }}</span></li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-6">
                <h2 class="text-base font-semibold text-white">4. Gestione preferenze</h2>
                <p class="mt-2 text-slate-300">
                    Puoi modificare in ogni momento la scelta sul consenso analytics.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" data-cookie-consent="accepted"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-blue px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-navy active:scale-95">
                        Accetta analytics
                    </button>
                    <button type="button" data-cookie-consent="rejected"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 active:scale-95">
                        Rifiuta analytics
                    </button>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Titolare: {{ $company }} · Contatto privacy: <a class="font-semibold text-brand-amber hover:text-brand-amber/90" href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>
                </p>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const setConsent = (value) => {
                try { localStorage.setItem('cookie_consent_analytics', value); } catch (e) {}
                window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: { analytics: value } }));
            };
            document.querySelectorAll('[data-cookie-consent]').forEach((btn) => {
                btn.addEventListener('click', () => setConsent(btn.getAttribute('data-cookie-consent')));
            });
        })();
    </script>
</x-layouts.central>

