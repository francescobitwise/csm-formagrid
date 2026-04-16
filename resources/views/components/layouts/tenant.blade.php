<!doctype html>
<html lang="it" class="dark" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ((string) tenant('organization_name').' · FormaGrid') }}</title>
    <meta name="description" content="{{ $description ?? ('Accedi alla piattaforma FormaGrid di '.(string) tenant('organization_name').'.') }}">
    {{-- Tenant apps are private; only the central marketing site should be indexed. --}}
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('brand/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const theme = stored || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="selection:bg-brand-blue/30 selection:text-white">
    @php
        $isAdminArea = request()->routeIs('tenant.admin.*');
        $tenantLogoUrl = \App\Support\TenantBranding::logoUrl();
        $navUser = auth()->user();
        $isTenantStaff = $navUser instanceof \App\Models\Tenant\User && $navUser->isStaffMember();
    @endphp
    <div class="noise-bg"></div>

    <header class="app-shell-header sticky top-0 z-40 w-full">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center gap-4 px-6">
            <div class="flex items-center gap-4">
                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl border border-brand-blue/30 bg-gradient-to-br from-brand-blue/25 to-brand-navy/25 shadow-lg shadow-black/15">
                    @if ($tenantLogoUrl)
                        <img src="{{ $tenantLogoUrl }}"
                             alt="{{ tenant('id') }}"
                             class="h-8 w-8 object-contain"
                             loading="eager">
                    @else
                        <img src="{{ asset('brand/formagrid-logo.svg') }}"
                             alt="{{ tenant('id') }}"
                             class="h-8 w-8 object-contain"
                             loading="eager">
                    @endif
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold tracking-tight text-white">{{ tenant('organization_name') }}</span>
                    <span class="text-xs text-slate-300">FormaGrid</span>
                </div>
            </div>

            @auth
                <nav aria-label="Navigazione principale" class="hidden flex-1 justify-center md:flex">
                    <div class="flex items-center gap-1 rounded-lg border border-white/10 bg-slate-900/50 p-1">
                        <a href="{{ route('tenant.dashboard') }}"
                           class="rounded-md px-4 py-1.5 text-sm font-medium {{ request()->routeIs('tenant.dashboard') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:text-white' }}">
                            I miei corsi
                        </a>
                        <a href="{{ route('tenant.courses.index') }}"
                           class="rounded-md px-4 py-1.5 text-sm font-medium {{ request()->routeIs('tenant.courses.index') || request()->routeIs('tenant.courses.show') || request()->routeIs('tenant.courses.enroll') || request()->routeIs('tenant.lessons.*') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:text-white' }}">
                            Catalogo
                        </a>
                    </div>
                </nav>
            @endauth
            <div class="ml-auto flex flex-shrink-0 items-center gap-2">
                @auth
                    @php
                        $u = auth()->user();
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
                            <div class="min-w-0 max-w-[10rem] leading-tight sm:max-w-[14rem]">
                                <div class="truncate text-sm font-semibold text-white">{{ $displayName !== '' ? $displayName : $displayEmail }}</div>
                                @if ($displayName !== '' && $displayEmail !== '')
                                    <div class="truncate text-xs text-slate-400">{{ $displayEmail }}</div>
                                @endif
                            </div>
                            <i class="nav-user-menu-caret ph ph-caret-down ml-1 shrink-0 text-base text-slate-400" aria-hidden="true"></i>
                        </summary>
                        <div class="nav-user-menu-panel absolute right-0 top-[calc(100%+0.5rem)] w-56 overflow-hidden rounded-xl border border-white/10 bg-slate-900/95 py-1 shadow-2xl shadow-black/40 backdrop-blur-xl">
                            @if ($isTenantStaff)
                                <a href="{{ route('tenant.admin.dashboard') }}"
                                   class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white {{ $isAdminArea ? 'bg-brand-blue/15 text-white' : '' }}">
                                    <i class="ph ph-gauge text-base" aria-hidden="true"></i>
                                    Area Admin
                                </a>
                            @endif
                            <form method="post" action="{{ route('tenant.logout') }}" class="@if ($isTenantStaff) border-t border-white/10 @endif">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                                    <i class="ph ph-sign-out text-base" aria-hidden="true"></i>
                                    Esci
                                </button>
                            </form>
                        </div>
                    </details>
                @elseif (! request()->routeIs('tenant.home') && ! request()->routeIs('tenant.login'))
                    <a href="{{ route('tenant.login') }}"
                       class="rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/10 sm:px-4 sm:text-sm">
                        Accedi
                    </a>
                @endauth
                <button type="button"
                        data-theme-toggle
                        title="Cambia tema"
                        aria-label="Cambia tema"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white transition hover:bg-white/20">
                    <i class="ph ph-sun text-xl theme-toggle-icon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-4rem)]">
        @if ($isAdminArea)
            <div class="mx-auto grid max-w-[1440px] gap-6 px-6 py-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                <aside class="admin-sidebar h-fit rounded-2xl p-4 lg:sticky lg:top-24">
                    <div class="mb-3 px-2">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menu Admin</div>
                        <div class="mt-1 text-sm font-medium text-slate-200">Gestione contenuti</div>
                    </div>

                    <nav aria-label="Navigazione area admin" class="space-y-1">
                        @tenantcan('admin.dashboard')
                            <a href="{{ route('tenant.admin.dashboard') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.dashboard') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-gauge text-base"></i>
                                Dashboard
                            </a>
                        @endtenantcan
                        @tenantcan('content.courses.read')
                            <a href="{{ route('tenant.admin.courses.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.courses.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-books text-base"></i>
                                Corsi
                            </a>
                        @endtenantcan
                        @if (Route::has('tenant.admin.modules.index'))
                            @tenantcan('content.modules.read')
                                <a href="{{ route('tenant.admin.modules.index') }}"
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.modules.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                    <i class="ph ph-squares-four text-base"></i>
                                    Moduli
                                </a>
                            @endtenantcan
                        @endif
                        @tenantcan('companies.manage')
                            <a href="{{ route('tenant.admin.companies.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.companies.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-buildings text-base"></i>
                                Aziende
                            </a>
                        @endtenantcan
                        @tenantcan('staff.manage')
                            <a href="{{ route('tenant.admin.staff.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.staff.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-users-three text-base"></i>
                                Staff
                            </a>
                        @endtenantcan
                        @tenantcan('settings.tenant')
                            <a href="{{ route('tenant.admin.profile.edit') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.profile.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-buildings text-base"></i>
                                Profilo
                            </a>
                        @endtenantcan
                        @tenantcan('audit.view')
                            <a href="{{ route('tenant.admin.audit-log.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.audit-log.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-list-dashes text-base"></i>
                                Registro attività
                            </a>
                        @endtenantcan
                        @tenantcan('compliance.manage')
                            <a href="{{ route('tenant.admin.compliance.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.admin.compliance.*') ? 'border border-brand-blue/30 bg-brand-blue/10 text-white shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                                <i class="ph ph-shield-check text-base"></i>
                                Compliance
                            </a>
                        @endtenantcan
                        @if (request()->routeIs('tenant.admin.courses.builder'))
                            <div class="mt-2 rounded-lg border border-brand-blue/35 bg-brand-blue/10 px-3 py-2 text-xs text-white/90">
                                Stai modificando il builder del corso corrente.
                            </div>
                        @endif
                    </nav>

                    <div class="mt-4 border-t border-white/10 pt-4">
                        @if (request()->routeIs('tenant.admin.modules.*') && Route::has('tenant.admin.modules.create'))
                            @tenantcan('content.modules.manage')
                                <a href="{{ route('tenant.admin.modules.create') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-blue px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-navy active:scale-95">
                                    <i class="ph ph-plus-circle"></i>
                                    Nuovo modulo
                                </a>
                            @endtenantcan
                        @else
                            @tenantcan('content.courses.manage')
                                <a href="{{ route('tenant.admin.courses.create') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-blue px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-navy active:scale-95">
                                    <i class="ph ph-plus-circle"></i>
                                    Nuovo corso
                                </a>
                            @endtenantcan
                        @endif
                    </div>
                </aside>

                <section class="admin-theme-scope min-w-0">
                    @if (session('toast'))
                        <div class="mb-6 rounded-xl border border-brand-amber/45 bg-brand-amber/10 px-4 py-3 text-sm text-brand-amber" data-auto-dismiss="3000">
                            {{ session('toast') }}
                        </div>
                    @endif
                    {{ $slot }}
                </section>
            </div>
        @else
            @if (session('toast'))
                <div class="mx-auto max-w-[1440px] px-6 pt-6">
                    <div class="rounded-xl border border-brand-amber/45 bg-brand-amber/10 px-4 py-3 text-sm text-brand-amber" data-auto-dismiss="3000">
                        {{ session('toast') }}
                    </div>
                </div>
            @endif
            {{ $slot }}
        @endif
    </main>
    <script>
        (() => {
            document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
                const ms = Number(el.getAttribute('data-auto-dismiss') || '0');
                if (!ms) return;
                window.setTimeout(() => {
                    el.style.transition = 'opacity 220ms ease, transform 220ms ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-4px)';
                    window.setTimeout(() => el.remove(), 240);
                }, ms);
            });

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
                localStorage.setItem('theme', next);
                syncLabel();
            });

            syncLabel();
        })();
    </script>
    @php
        $tenantGa4 = (string) (config('analytics.tenant_ga4_measurement_id') ?? '');
        $cookiePolicyUrl = '#';
    @endphp
    @if (filled($tenantGa4))
        <x-cookie-analytics-consent
            :ga4-id="$tenantGa4"
            :storage-key="'fg_cookie_analytics_'.(string) tenant('id')"
            :cookie-policy-url="$cookiePolicyUrl"
        />
    @endif
    @stack('scripts')
</body>
</html>

