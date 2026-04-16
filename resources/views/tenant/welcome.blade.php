<x-layouts.tenant :title="tenant('id').' — '.config('app.name')">
    @auth
        <div class="mx-auto max-w-[1440px] px-6 py-12">
            <div class="glass-panel rounded-2xl p-8 md:p-10">
                <h1 class="text-2xl font-semibold tracking-tight text-white md:text-3xl">Bentornato</h1>
                <p class="mt-2 max-w-xl text-slate-400">
                    Scegli dove andare: i tuoi corsi, il catalogo o l’area amministrazione (se abilitata).
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('tenant.dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        <i class="ph ph-books text-lg"></i>
                        I miei corsi
                    </a>
                    <a href="{{ route('tenant.courses.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        <i class="ph ph-squares-four text-lg"></i>
                        Catalogo
                    </a>
                    @if (auth()->user()->isStaffMember())
                        <a href="{{ route('tenant.admin.dashboard') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-brand-blue/35 bg-brand-blue/10 px-6 py-3 text-sm font-semibold text-white/90 transition hover:bg-brand-blue/15">
                            <i class="ph ph-gauge text-lg"></i>
                            Area admin
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="relative mx-auto max-w-2xl px-6 py-16 md:py-24">
            <div class="pointer-events-none absolute inset-x-0 -top-24 flex justify-center opacity-40 blur-3xl">
                <div class="h-64 w-64 rounded-full bg-gradient-to-tr from-brand-blue/35 to-brand-navy/30"></div>
            </div>
            <div class="relative text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-amber/90">FormaGrid · organizzazione</p>
                <h1 class="mt-5 text-4xl font-bold tracking-tight text-white md:text-5xl">{{ tenant('id') }}</h1>
                <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-slate-400">
                    Catalogo corsi, iscrizioni e tracciamento progressi. Un solo accesso: dopo il login sarai indirizzato
                    all’area adatta al tuo profilo (allievo o staff).
                </p>
                <div class="mt-10">
                    <a href="{{ route('tenant.login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-blue px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        <i class="ph ph-sign-in text-xl"></i>
                        Accedi alla piattaforma
                    </a>
                </div>
                <p class="mx-auto mt-8 max-w-lg text-sm leading-relaxed text-slate-500">
                    Gli account non si registrano da soli: l’amministratore della tua organizzazione crea gli utenti e invia le credenziali.
                </p>
            </div>
        </div>
    @endauth
</x-layouts.tenant>
