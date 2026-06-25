<!doctype html>
<html lang="it" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ((string) tenant('organization_name').' · FormaGrid') }}</title>
    <meta name="description" content="{{ $description ?? ('Accedi alla piattaforma FormaGrid di '.(string) tenant('organization_name').'.') }}">
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
<body class="min-h-screen bg-base-200">
    @php
        $isAdminArea = request()->routeIs('tenant.admin.*');
        $tenantLogoUrl = \App\Support\TenantBranding::logoUrl();
        $navUser = auth()->user();
        $isTenantStaff = $navUser instanceof \App\Models\Tenant\User && $navUser->isStaffMember();
    @endphp

    <div @class(['drawer' => $isAdminArea, 'lg:drawer-open' => $isAdminArea])>
        @if ($isAdminArea)
            <input id="admin-drawer" type="checkbox" class="drawer-toggle" />
        @endif

        <div @class(['drawer-content flex min-h-screen flex-col' => $isAdminArea, 'flex min-h-screen flex-col' => ! $isAdminArea])>
            {{-- Navbar --}}
            <div class="navbar sticky top-0 z-40 border-b border-base-300 bg-base-100/90 px-4 backdrop-blur lg:px-6">
                @if ($isAdminArea)
                    <div class="flex-none lg:hidden">
                        <label for="admin-drawer" class="btn btn-square btn-ghost" aria-label="Apri menu admin">
                            <i class="ph ph-list text-xl"></i>
                        </label>
                    </div>
                @endif

                <div class="flex flex-1 items-center gap-3">
                    <div class="avatar placeholder">
                        <div class="w-9 rounded-xl bg-primary/20 text-primary">
                            @if ($tenantLogoUrl)
                                <img src="{{ $tenantLogoUrl }}" alt="{{ tenant('id') }}" class="object-contain p-1">
                            @else
                                <img src="{{ asset('brand/formagrid-logo.svg') }}" alt="{{ tenant('id') }}" class="object-contain p-1">
                            @endif
                        </div>
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold">{{ tenant('organization_name') }}</div>
                        <div class="text-xs text-base-content/60">FormaGrid</div>
                    </div>
                </div>

                @auth
                    <nav aria-label="Navigazione principale" class="hidden flex-none md:flex">
                        <ul class="menu menu-horizontal rounded-box bg-base-200 px-1">
                            <li>
                                <a href="{{ route('tenant.dashboard') }}"
                                   @class(['active' => request()->routeIs('tenant.dashboard')])>
                                    I miei corsi
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('tenant.courses.index') }}"
                                   @class(['active' => request()->routeIs('tenant.courses.index') || request()->routeIs('tenant.courses.show') || request()->routeIs('tenant.courses.enroll') || request()->routeIs('tenant.lessons.*')])>
                                    Catalogo
                                </a>
                            </li>
                        </ul>
                    </nav>
                @endauth

                <div class="flex flex-none items-center gap-2">
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
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost gap-2 px-2" aria-label="Menu account">
                                <div class="avatar placeholder">
                                    <div class="w-9 rounded-full bg-primary/20 text-xs font-bold text-primary">
                                        <span>{{ $initials }}</span>
                                    </div>
                                </div>
                                <div class="hidden min-w-0 max-w-[10rem] text-left leading-tight sm:block lg:max-w-[14rem]">
                                    <div class="truncate text-sm font-semibold">{{ $displayName !== '' ? $displayName : $displayEmail }}</div>
                                    @if ($displayName !== '' && $displayEmail !== '')
                                        <div class="truncate text-xs text-base-content/60">{{ $displayEmail }}</div>
                                    @endif
                                </div>
                                <i class="ph ph-caret-down text-base text-base-content/60"></i>
                            </div>
                            <ul tabindex="0" class="menu dropdown-content z-50 mt-2 w-56 rounded-box bg-base-100 p-2 shadow-lg">
                                @if ($isTenantStaff)
                                    <li>
                                        <a href="{{ route('tenant.admin.dashboard') }}" @class(['active' => $isAdminArea])>
                                            <i class="ph ph-gauge"></i>
                                            Area Admin
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <form method="post" action="{{ route('tenant.logout') }}">
                                        @csrf
                                        <button type="submit">
                                            <i class="ph ph-sign-out"></i>
                                            Esci
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @elseif (! request()->routeIs('tenant.home') && ! request()->routeIs('tenant.login'))
                        <a href="{{ route('tenant.login') }}" class="btn btn-outline btn-sm">Accedi</a>
                    @endauth

                    <label class="swap swap-rotate btn btn-ghost btn-circle" title="Cambia tema" aria-label="Cambia tema">
                        <input type="checkbox" data-theme-toggle class="theme-controller" value="light" />
                        <i class="ph ph-sun swap-off text-xl"></i>
                        <i class="ph ph-moon swap-on text-xl"></i>
                    </label>
                </div>
            </div>

            {{-- Main content --}}
            <main class="flex-1">
                @if ($isAdminArea)
                    <div class="mx-auto max-w-[1440px] px-4 py-6 lg:px-6">
                        <x-ui.flash />
                        {{ $slot }}
                    </div>
                @else
                    @if (session('toast'))
                        <div class="mx-auto max-w-[1440px] px-4 pt-6 lg:px-6">
                            <x-ui.alert type="warning" dismiss="3000">{{ session('toast') }}</x-ui.alert>
                        </div>
                    @endif
                    {{ $slot }}
                @endif
            </main>
        </div>

        {{-- Admin sidebar drawer --}}
        @if ($isAdminArea)
            <div class="drawer-side z-50">
                <label for="admin-drawer" aria-label="Chiudi menu" class="drawer-overlay"></label>
                <aside class="menu min-h-full w-64 bg-base-100 p-4 text-base-content">
                    <div class="mb-4 px-2">
                        <div class="text-xs font-semibold uppercase tracking-wider text-base-content/50">Menu Admin</div>
                        <div class="mt-1 text-sm font-medium">Gestione contenuti</div>
                    </div>

                    <ul>
                        @tenantcan('admin.dashboard')
                            <li>
                                <a href="{{ route('tenant.admin.dashboard') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.dashboard')])>
                                    <i class="ph ph-gauge"></i> Dashboard
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('content.courses.read')
                            <li>
                                <a href="{{ route('tenant.admin.courses.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.courses.*')])>
                                    <i class="ph ph-books"></i> Corsi
                                </a>
                            </li>
                        @endtenantcan
                        @if (Route::has('tenant.admin.modules.index'))
                            @tenantcan('content.modules.read')
                                <li>
                                    <a href="{{ route('tenant.admin.modules.index') }}"
                                       @class(['active' => request()->routeIs('tenant.admin.modules.*')])>
                                        <i class="ph ph-squares-four"></i> Moduli
                                    </a>
                                </li>
                            @endtenantcan
                        @endif
                        @tenantcan('companies.manage')
                            <li>
                                <a href="{{ route('tenant.admin.companies.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.companies.*')])>
                                    <i class="ph ph-buildings"></i> Aziende
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('learners.manage')
                            <li>
                                <a href="{{ route('tenant.admin.learners.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.learners.*')])>
                                    <i class="ph ph-users"></i> Utenti
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('staff.manage')
                            <li>
                                <a href="{{ route('tenant.admin.staff.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.staff.*')])>
                                    <i class="ph ph-users-three"></i> Staff
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('settings.tenant')
                            <li>
                                <a href="{{ route('tenant.admin.profile.edit') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.profile.*')])>
                                    <i class="ph ph-buildings"></i> Profilo
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('audit.view')
                            <li>
                                <a href="{{ route('tenant.admin.audit-log.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.audit-log.*')])>
                                    <i class="ph ph-list-dashes"></i> Registro attività
                                </a>
                            </li>
                        @endtenantcan
                        @tenantcan('compliance.manage')
                            <li>
                                <a href="{{ route('tenant.admin.compliance.index') }}"
                                   @class(['active' => request()->routeIs('tenant.admin.compliance.*')])>
                                    <i class="ph ph-shield-check"></i> Compliance
                                </a>
                            </li>
                        @endtenantcan
                    </ul>

                    @if (request()->routeIs('tenant.admin.courses.builder'))
                        <div class="alert alert-info mt-4 text-xs">
                            Stai modificando il builder del corso corrente.
                        </div>
                    @endif

                    <div class="mt-4 border-t border-base-300 pt-4">
                        @if (request()->routeIs('tenant.admin.modules.*') && Route::has('tenant.admin.modules.create'))
                            @tenantcan('content.modules.manage')
                                <a href="{{ route('tenant.admin.modules.create') }}" class="btn btn-primary btn-block">
                                    <i class="ph ph-plus-circle"></i> Nuovo modulo
                                </a>
                            @endtenantcan
                        @else
                            @tenantcan('content.courses.manage')
                                <a href="{{ route('tenant.admin.courses.create') }}" class="btn btn-primary btn-block">
                                    <i class="ph ph-plus-circle"></i> Nuovo corso
                                </a>
                            @endtenantcan
                        @endif
                    </div>
                </aside>
            </div>
        @endif
    </div>

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
            const toggle = document.querySelector('[data-theme-toggle]');
            if (!toggle) return;

            const syncToggle = () => {
                const t = root.getAttribute('data-theme') || 'dark';
                toggle.checked = t === 'light';
            };

            toggle.addEventListener('change', () => {
                const next = toggle.checked ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            });

            syncToggle();
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
