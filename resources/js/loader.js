/**
 * Loader full-screen: navigazione tra pagine + richieste AJAX (fetch + axios).
 * Escludi il loader: header X-Skip-Loader: 1 oppure attributo data-no-loader su <a> / <form>.
 */

const AJAX_SHOW_DELAY_MS = 140;
const LOADER_ID = 'app-global-loader';

let ajaxPending = 0;
let ajaxShowTimer = null;
let originalFetch = window.fetch;
/** Timer: click su link che avvia solo download (stessa pagina) non scatena pageshow → nascondi dopo un attimo. */
let navigationLoaderFallbackTimer = null;

function getOverlay() {
    return document.getElementById(LOADER_ID);
}

function showOverlay() {
    const el = getOverlay();
    if (el) {
        el.classList.remove('pointer-events-none', 'opacity-0');
        el.classList.add('opacity-100');
        el.setAttribute('aria-hidden', 'false');
    }
}

function hideOverlay() {
    const el = getOverlay();
    if (el) {
        el.classList.add('pointer-events-none', 'opacity-0');
        el.classList.remove('opacity-100');
        el.setAttribute('aria-hidden', 'true');
    }
}

function clearNavigationLoaderFallback() {
    if (navigationLoaderFallbackTimer !== null) {
        window.clearTimeout(navigationLoaderFallbackTimer);
        navigationLoaderFallbackTimer = null;
    }
}

function showOverlayForFullNavigation() {
    showOverlay();
    clearNavigationLoaderFallback();
    navigationLoaderFallbackTimer = window.setTimeout(() => {
        navigationLoaderFallbackTimer = null;
        hideOverlay();
    }, 2500);
}

function beginAjax() {
    ajaxPending += 1;
    if (ajaxPending === 1) {
        ajaxShowTimer = window.setTimeout(() => {
            if (ajaxPending > 0) {
                showOverlay();
            }
        }, AJAX_SHOW_DELAY_MS);
    }
}

function endAjax() {
    ajaxPending = Math.max(0, ajaxPending - 1);
    if (ajaxPending === 0) {
        if (ajaxShowTimer) {
            window.clearTimeout(ajaxShowTimer);
            ajaxShowTimer = null;
        }
        hideOverlay();
    }
}

function headersSkipLoader(input, init) {
    try {
        if (input instanceof Request) {
            return input.headers.get('X-Skip-Loader') === '1';
        }
        if (init?.headers) {
            const h = new Headers(init.headers);
            return h.get('X-Skip-Loader') === '1';
        }
    } catch {
        /* ignore */
    }
    return false;
}

function installFetchInterceptor() {
    window.fetch = function (input, init) {
        if (headersSkipLoader(input, init)) {
            return originalFetch.call(this, input, init);
        }

        beginAjax();
        return originalFetch.call(this, input, init).finally(() => {
            endAjax();
        });
    };
}

function installAxiosInterceptor() {
    if (typeof window.axios === 'undefined') {
        return;
    }

    window.axios.interceptors.request.use((config) => {
        const h = config.headers;
        const skip =
            (typeof h?.get === 'function' && h.get('X-Skip-Loader') === '1') ||
            h?.['X-Skip-Loader'] === '1' ||
            h?.['x-skip-loader'] === '1';
        config.__appLoader = !skip;
        if (!skip) {
            beginAjax();
        }
        return config;
    });

    window.axios.interceptors.response.use(
        (response) => {
            if (response.config?.__appLoader) {
                endAjax();
            }
            return response;
        },
        (error) => {
            if (error.config?.__appLoader) {
                endAjax();
            }
            return Promise.reject(error);
        },
    );
}

function installNavigationLoader() {
    document.addEventListener(
        'click',
        (e) => {
            const a = e.target.closest?.('a');
            if (!a || !a.href) {
                return;
            }
            if (e.defaultPrevented) {
                return;
            }
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
                return;
            }
            if (a.target === '_blank' || a.hasAttribute('download')) {
                return;
            }
            if (a.getAttribute('data-no-loader') !== null) {
                return;
            }

            const url = new URL(a.href, window.location.href);
            if (url.origin !== window.location.origin) {
                return;
            }

            const hrefAttr = a.getAttribute('href') || '';
            if (hrefAttr === '#' || hrefAttr.startsWith('#')) {
                return;
            }

            showOverlayForFullNavigation();
        },
        true,
    );

    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (form.hasAttribute('data-no-loader')) {
                return;
            }
            if (e.defaultPrevented) {
                return;
            }
            showOverlayForFullNavigation();
        },
        true,
    );

    window.addEventListener('pageshow', (e) => {
        clearNavigationLoaderFallback();
        hideOverlay();
        ajaxPending = 0;
        if (ajaxShowTimer) {
            window.clearTimeout(ajaxShowTimer);
            ajaxShowTimer = null;
        }
        if (e.persisted) {
            /* bfcache */
        }
    });
}

function injectMarkup() {
    if (document.getElementById(LOADER_ID)) {
        return;
    }
    const wrap = document.createElement('div');
    wrap.id = LOADER_ID;
    wrap.className =
        'pointer-events-none fixed inset-0 z-[200] flex items-center justify-center opacity-0 transition-opacity duration-200';
    wrap.setAttribute('role', 'status');
    wrap.setAttribute('aria-live', 'polite');
    wrap.setAttribute('aria-hidden', 'true');
    wrap.innerHTML = `
        <div class="app-loader-backdrop absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]"></div>
        <div class="relative flex flex-col items-center gap-3 rounded-2xl border border-cyan-400/25 bg-slate-900/90 px-8 py-6 shadow-2xl shadow-cyan-950/40">
            <div class="app-loader-spinner h-10 w-10 rounded-full border-2 border-cyan-400/25 border-t-cyan-400"></div>
            <span class="text-xs font-medium uppercase tracking-wider text-cyan-100/90">Caricamento…</span>
        </div>
    `;
    document.body.appendChild(wrap);
}

export function initPageLoader() {
    installFetchInterceptor();
    installAxiosInterceptor();

    const finish = () => {
        injectMarkup();
        installNavigationLoader();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', finish, { once: true });
    } else {
        finish();
    }
}
