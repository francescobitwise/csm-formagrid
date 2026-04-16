@props([
    'ga4Id' => '',
    'storageKey' => 'cookie_consent_analytics',
    'cookiePolicyUrl' => '#',
])
@if (filled($ga4Id))
    <div id="cookie_banner"
         class="fixed inset-x-0 bottom-0 z-50 hidden border-t border-slate-200 bg-white/90 backdrop-blur dark:border-white/10 dark:bg-slate-950/95">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-6 sm:py-5">
            <div class="text-[15px] leading-relaxed text-slate-700 dark:text-slate-100 sm:text-base">
                Usiamo cookie tecnici e, con il tuo consenso, cookie di <strong class="text-slate-900 dark:text-white">analytics</strong>.
                <a class="font-semibold text-brand-amber underline decoration-brand-amber/40 underline-offset-4 hover:text-brand-amber/90"
                   href="{{ $cookiePolicyUrl }}">
                    Leggi la Cookie Policy
                </a>.
            </div>
            <div class="flex flex-wrap gap-2 sm:shrink-0">
                <button type="button" data-cookie-accept
                        class="inline-flex items-center justify-center rounded-xl bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy active:scale-95 sm:text-base">
                    Accetta analytics
                </button>
                <button type="button" data-cookie-reject
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 active:scale-95 dark:border-white/15 dark:bg-white/5 dark:text-slate-100 dark:hover:bg-white/10 sm:text-base">
                    Rifiuta
                </button>
            </div>
        </div>
    </div>

    <script type="application/json" id="ga4_measurement_json">@json((string) $ga4Id)</script>

    <script>
        (() => {
            const GA4_ID = JSON.parse(document.getElementById('ga4_measurement_json')?.textContent || '""') || '';
            const KEY = @json($storageKey);

            const banner = document.getElementById('cookie_banner');
            const acceptBtn = document.querySelector('[data-cookie-accept]');
            const rejectBtn = document.querySelector('[data-cookie-reject]');

            const getConsent = () => {
                try { return localStorage.getItem(KEY); } catch (e) { return null; }
            };
            const setConsent = (value) => {
                try { localStorage.setItem(KEY, value); } catch (e) {}
                window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: { analytics: value } }));
            };

            const loadGa4 = () => {
                if (!GA4_ID) return;
                if (window.__ga4_loaded) return;
                window.__ga4_loaded = true;

                window.dataLayer = window.dataLayer || [];
                function gtag(){ dataLayer.push(arguments); }
                window.gtag = window.gtag || gtag;

                const s = document.createElement('script');
                s.async = true;
                s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(GA4_ID)}`;
                document.head.appendChild(s);

                gtag('js', new Date());
                gtag('config', GA4_ID, { anonymize_ip: true });
            };

            const sync = () => {
                const c = getConsent();
                if (!c && banner) {
                    banner.classList.remove('hidden');
                    return;
                }
                if (banner) banner.classList.add('hidden');
                if (c === 'accepted') loadGa4();
            };

            if (acceptBtn) acceptBtn.addEventListener('click', () => { setConsent('accepted'); sync(); });
            if (rejectBtn) rejectBtn.addEventListener('click', () => { setConsent('rejected'); sync(); });

            window.addEventListener('cookie-consent-changed', () => sync());
            sync();
        })();
    </script>
@endif
