@props([
    'title' => null,
    'description' => null,
    'ogTitle' => null,
    'ogDescription' => null,
])
@php
    $pageTitle = $title ?? config('app.name');
    $metaDescription = $description ?? 'Piattaforma e-learning per aziende ed enti: corsi SCORM e video, iscrizioni, progressi e report. Registrazione in autonomia, ambiente dedicato per organizzazione, pagamento sicuro.';
    $socialTitle = $ogTitle ?? $pageTitle;
    $socialDescription = $ogDescription ?? $metaDescription;
@endphp
<!doctype html>
<html lang="it" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:title" content="{{ $socialTitle }}">
    <meta property="og:description" content="{{ $socialDescription }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $socialTitle }}">
    <meta name="twitter:description" content="{{ $socialDescription }}">
    @if (request()->routeIs('central.home', 'central.register', 'central.legal.*'))
        @php
            $ldOrg = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => (string) config('legal.company_name', config('app.name')),
                'url' => url('/'),
            ];
            $ldEmail = trim((string) config('legal.email', ''));
            if ($ldEmail !== '') {
                $ldOrg['email'] = $ldEmail;
            }
            $ldAddr = trim((string) config('legal.address', ''));
            if ($ldAddr !== '') {
                $ldOrg['address'] = $ldAddr;
            }
        @endphp
        <script type="application/ld+json">{!! json_encode($ldOrg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <link rel="icon" href="{{ asset('brand/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const theme = stored || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
        })();
    </script>
</head>
<body class="selection:bg-brand-blue/30 selection:text-white">
    <div class="noise-bg"></div>

    <header class="app-shell-header sticky top-0 z-40 w-full">
        <div class="mx-auto flex h-20 max-w-[1440px] items-center justify-between px-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('central.home') }}" class="focus:outline-none">
                    <img src="{{ asset('brand/formagrid-logo.svg') }}"
                         alt="{{ config('app.name', 'FormaGrid') }}"
                         class="hidden h-10 w-auto object-contain md:block">
                </a>
                <img src="{{ asset('brand/favicon.svg') }}"
                     alt="{{ config('app.name', 'FormaGrid') }}"
                     class="h-9 w-9 object-contain md:hidden"
                     loading="eager">
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-4">
                @if (request()->routeIs('central.home'))
                    <a href="#come-funziona"
                       class="hidden rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/10 sm:inline-block sm:text-sm">
                        Come funziona
                    </a>
                    <a href="#prezzi"
                       class="hidden rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/10 sm:inline-block sm:text-sm">
                        Prezzi
                    </a>
                @endif
                @auth('central')
                    @php
                        $u = auth('central')->user();
                        $displayName = is_object($u) ? (string) ($u->name ?? '') : '';
                        $displayEmail = is_object($u) ? (string) ($u->email ?? '') : '';
                        $seed = trim($displayName) !== '' ? $displayName : $displayEmail;
                        $parts = preg_split('/\s+/', trim($seed)) ?: [];
                        $initials = '';
                        if (count($parts) >= 2) {
                            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
                        } elseif (count($parts) === 1 && $parts[0] !== '') {
                            $initials = mb_strtoupper(mb_substr($parts[0], 0, 2));
                        }
                        $initials = $initials !== '' ? $initials : 'U';
                    @endphp

                    <details class="nav-user-menu relative z-50">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 transition hover:bg-white/10 [&::-webkit-details-marker]:hidden"
                            aria-label="Menu account">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-blue/30 to-brand-navy/30 text-xs font-bold text-white ring-1 ring-white/10">
                                {{ $initials }}
                            </div>
                            <div class="hidden min-w-0 max-w-[10rem] leading-tight sm:block sm:max-w-[14rem]">
                                <div class="truncate text-sm font-semibold text-white">{{ $displayName !== '' ? $displayName : $displayEmail }}</div>
                                @if ($displayName !== '' && $displayEmail !== '')
                                    <div class="truncate text-xs text-slate-400">{{ $displayEmail }}</div>
                                @endif
                            </div>
                            <i class="nav-user-menu-caret ph ph-caret-down ml-1 shrink-0 text-base text-slate-400" aria-hidden="true"></i>
                        </summary>
                        <div class="nav-user-menu-panel absolute right-0 top-[calc(100%+0.5rem)] w-56 overflow-hidden rounded-xl border border-white/10 bg-slate-900/95 py-1 shadow-2xl shadow-black/40 backdrop-blur-xl">
                            <a href="{{ route('central.dashboard') }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white {{ request()->routeIs('central.dashboard') ? 'bg-brand-blue/15 text-white' : '' }}">
                                <i class="ph ph-gauge text-base" aria-hidden="true"></i>
                                Dashboard
                            </a>
                            <a href="{{ route('central.tenants.index') }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white {{ request()->routeIs('central.tenants.*') ? 'bg-brand-blue/15 text-white' : '' }}">
                                <i class="ph ph-buildings text-base" aria-hidden="true"></i>
                                Organizzazioni
                            </a>
                            <form method="post" action="{{ route('central.logout') }}" class="border-t border-white/10">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                                    <i class="ph ph-sign-out text-base" aria-hidden="true"></i>
                                    Esci
                                </button>
                            </form>
                        </div>
                    </details>
                @endauth
                <button type="button"
                        data-theme-toggle
                        title="Cambia tema"
                        aria-label="Cambia tema"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white transition hover:bg-white/20">
                    <i class="ph ph-sun text-xl theme-toggle-icon" aria-hidden="true"></i>
                </button>
                <a href="{{ route('central.register') }}"
                   class="rounded-xl bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95 sm:px-5">
                    Inizia subito
                </a>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-5rem)]">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white/80 dark:border-white/10 dark:bg-slate-950/60">
        <div class="mx-auto max-w-[1440px] px-6 py-10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                @php
                    $legalCompany = (string) config('legal.company_name', config('app.name'));
                    $legalVat = trim((string) config('legal.vat', ''));
                    $legalAddress = trim((string) config('legal.address', ''));
                @endphp
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="font-semibold text-slate-900 dark:text-slate-200">{{ $legalCompany }}</span>
                        @if ($legalVat !== '')
                            <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                            <span class="text-slate-600 dark:text-slate-400">P.IVA {{ $legalVat }}</span>
                        @endif
                    </div>
                    @if ($legalAddress !== '')
                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-500 dark:text-slate-500">{{ $legalAddress }}</p>
                    @endif
                </div>
                <nav class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    <a class="text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white" href="{{ route('central.legal.privacy') }}">Privacy</a>
                    <a class="text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white" href="{{ route('central.legal.cookies') }}">Cookie</a>
                    <a class="text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white" href="{{ route('central.legal.terms') }}">Termini</a>
                    <a class="text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white" href="{{ route('central.legal.dpa') }}">DPA</a>
                    <a class="text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white" href="{{ route('central.legal.subprocessors') }}">Sub‑responsabili</a>
                </nav>
            </div>
        </div>
    </footer>

    <x-cookie-analytics-consent
        :ga4-id="(string) (config('analytics.ga4_measurement_id') ?? '')"
        storage-key="cookie_consent_analytics_central"
        :cookie-policy-url="route('central.legal.cookies')"
    />

    <script>
        (() => {
            const root = document.documentElement;
            const btn = document.querySelector('[data-theme-toggle]');
            if (!btn) return;

            const icon = btn.querySelector('.theme-toggle-icon');
            const syncLabel = () => {
                const t = root.getAttribute('data-theme') || 'dark';
                if (icon) {
                    icon.className = t === 'dark' ? 'ph ph-sun text-xl theme-toggle-icon' : 'ph ph-moon text-xl theme-toggle-icon';
                    icon.setAttribute('aria-hidden', 'true');
                }
                btn.setAttribute('aria-label', t === 'dark' ? 'Attiva tema chiaro' : 'Attiva tema scuro');
                btn.setAttribute('title', t === 'dark' ? 'Tema chiaro' : 'Tema scuro');
            };

            btn.addEventListener('click', () => {
                const curr = root.getAttribute('data-theme') || 'dark';
                const next = curr === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                root.classList.toggle('dark', next === 'dark');
                localStorage.setItem('theme', next);
                syncLabel();
            });

            syncLabel();
        })();
    </script>
</body>
</html>

