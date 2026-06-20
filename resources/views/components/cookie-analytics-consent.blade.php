@props([
    'ga4Id' => '',
    'storageKey' => 'cookie_consent_analytics',
    'cookiePolicyUrl' => '#',
])
@if (filled($ga4Id))
    <div id="cookie_banner"
         class="fixed inset-x-0 bottom-0 z-50 hidden border-t border-base-300 bg-base-100/95 backdrop-blur">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-6 sm:py-5">
            <div class="text-[15px] leading-relaxed text-base-content sm:text-base">
                Usiamo cookie tecnici e, con il tuo consenso, cookie di <strong>analytics</strong>.
                <a class="link link-primary font-semibold"
                   href="{{ $cookiePolicyUrl }}">
                    Leggi la Cookie Policy
                </a>.
            </div>
            <div class="flex flex-wrap gap-2 sm:shrink-0">
                <button type="button" data-cookie-accept class="btn btn-primary btn-sm sm:btn-md">
                    Accetta analytics
                </button>
                <button type="button" data-cookie-reject class="btn btn-outline btn-sm sm:btn-md">
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
