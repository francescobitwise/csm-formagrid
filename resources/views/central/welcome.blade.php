<x-layouts.central
    :title="config('app.name').' — Spazio e-learning per organizzazioni'"
    description="Attiva in autonomia il tuo spazio di formazione: corsi (SCORM, video), iscrizioni, progressi e report. Dati isolati per organizzazione."
    ogTitle="Il tuo spazio e-learning, pronto in minuti"
    ogDescription="Corsi, iscritti e report nel tuo spazio dedicato: dati separati per organizzazione, attivazione autonoma, pagamento sicuro con Stripe."
>
    @php
        $plans = config('tenant_plans.plans', []);
        $trialPlanKey = (string) config('marketing.trial_highlight_plan', 'basic');
        $trialDays = (int) data_get($plans, $trialPlanKey.'.trial_days', 0);
        $socialCards = config('marketing.social_proof_cards', []);
    @endphp

    {{-- Hero --}}
    <section class="relative z-10 overflow-hidden border-b border-white/5">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_90%_60%_at_50%_-15%,rgb(26_109_191/0.22),transparent_55%),radial-gradient(ellipse_60%_40%_at_100%_50%,rgb(12_47_92/0.14),transparent),radial-gradient(ellipse_60%_45%_at_10%_70%,rgb(245_158_11/0.10),transparent_60%)]"></div>
        <div class="relative mx-auto flex max-w-[1200px] flex-col items-center px-4 pb-16 pt-12 text-center sm:px-6 md:pb-28 md:pt-20">
  
            <h1 class="mb-5 max-w-4xl px-1 text-3xl font-bold leading-[1.15] tracking-tight text-white sm:text-4xl md:mb-6 md:text-5xl md:leading-[1.1] lg:text-6xl">
                Il tuo spazio di formazione,<br>
                <span class="text-brand-blue">pronto in minuti</span>
            </h1>

            <p class="mb-8 max-w-2xl px-1 text-[15px] leading-relaxed text-slate-300 sm:text-base md:text-lg">
                Con <strong class="font-semibold text-white">{{ config('app.name', 'FormaGrid') }}</strong> la tua <strong class="text-white">organizzazione</strong> si attiva in autonomia e ottiene uno <strong class="text-white">spazio dedicato</strong>.
                <br class="hidden sm:block">
                Dall’area admin <strong class="text-white">carichi corsi</strong> (video, SCORM, documenti) e gestisci iscrizioni.
                <br class="hidden sm:block">
                <strong class="text-white">Progressi e report</strong> restano chiari — e i dati non si mischiano con quelli di altre organizzazioni.
            </p>

            @if ($trialDays > 0)
                <p class="-mt-3 mb-7 max-w-xl px-2 text-sm leading-relaxed text-slate-300">
                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-amber/30 bg-brand-amber/10 px-4 py-2 text-xs font-semibold text-brand-amber">
                        <i class="ph ph-timer" aria-hidden="true"></i>
                        {{ $trialDays }} {{ $trialDays === 1 ? 'giorno' : 'giorni' }} di prova gratuita sul piano {{ data_get($plans, $trialPlanKey.'.label', ucfirst($trialPlanKey)) }}
                    </span>
                </p>
            @endif

            <div class="flex w-full max-w-lg flex-col items-stretch gap-3 sm:max-w-none sm:flex-row sm:justify-center sm:gap-4">
                <a href="{{ route('central.register') }}"
                   class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-brand-blue px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                    Inizia subito <i class="ph ph-arrow-right font-bold" aria-hidden="true"></i>
                </a>
                <a href="#come-funziona"
                   class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-8 py-3.5 text-base font-medium text-white backdrop-blur transition hover:bg-white/10 active:scale-95">
                    Scopri come funziona <i class="ph ph-arrow-down" aria-hidden="true"></i>
                </a>
            </div>
            <p class="mt-5 max-w-md px-2 text-xs leading-relaxed text-slate-500">Abbonamento via Stripe · prezzi IVA esclusa ove applicabile</p>
        </div>
    </section>

    {{-- Social proof --}}
    <section class="keep-white-in-light relative z-10 border-b border-white/5 bg-gradient-to-b from-slate-950 to-slate-950/60 py-10">
        <div class="mx-auto max-w-[1200px] px-4 sm:px-6">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Scelto da organizzazioni</p>
                <p class="mt-2 text-sm text-slate-300 sm:text-base">
                    Usato per onboarding, compliance e corsi a catalogo, con spazi dedicati e dati separati per organizzazione.
                </p>
                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($socialCards as $card)
                        <div class="rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-4 text-left ring-1 ring-white/[0.04] backdrop-blur-sm">
                            <div class="text-sm font-semibold text-white">{{ $card['title'] ?? '' }}</div>
                            <div class="mt-1 text-xs leading-relaxed text-slate-400">{{ $card['body'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-slate-500">Funzioni e piani possono evolvere: ti avvisiamo sempre in modo trasparente.</p>
            </div>
        </div>
    </section>

    {{-- Prodotto --}}
    <section id="come-funziona" class="keep-white-in-light relative z-10 scroll-mt-28 border-b border-brand-navy/25 bg-gradient-to-b from-slate-950 via-slate-900/85 to-slate-950 py-14 md:py-24">
        <div class="mx-auto max-w-[1200px] px-4 sm:px-6">
            <div class="mx-auto mb-10 max-w-2xl text-center md:mb-12">
                <h2 class="welcome-section-title text-xl font-bold tracking-tight text-white sm:text-2xl md:text-3xl">Per aziende e per enti: cosa ottieni</h2>
                <p class="welcome-lead mt-3 text-sm sm:text-base">Un’unica piattaforma per gestire corsi e partecipanti. Il tuo spazio è solo tuo: accessi, contenuti, progressi e report, con limiti e funzioni legati al piano.</p>
            </div>

            <div class="mx-auto mb-8 grid max-w-4xl grid-cols-1 gap-4 sm:mb-10 sm:grid-cols-2 sm:gap-5">
                <div class="welcome-section-card rounded-2xl border border-white/10 bg-slate-900/55 p-5 shadow-lg shadow-black/20 ring-1 ring-white/[0.04] backdrop-blur-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-semibold uppercase tracking-wider text-brand-amber/90">Se sei un’azienda</div>
                        <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200">
                            <i class="ph ph-buildings" aria-hidden="true"></i> HR / Compliance
                        </span>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Onboarding e aggiornamento continuo, con tracciamento completamenti.</span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Ruoli staff e gestione utenti via CSV per scalare velocemente.</span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Report chiari per audit e compliance.</span></li>
                    </ul>
                </div>
                <div class="welcome-section-card rounded-2xl border border-white/10 bg-slate-900/55 p-5 shadow-lg shadow-black/20 ring-1 ring-white/[0.04] backdrop-blur-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-semibold uppercase tracking-wider text-brand-amber/90">Se sei un ente</div>
                        <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200">
                            <i class="ph ph-graduation-cap" aria-hidden="true"></i> Catalogo corsi
                        </span>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Catalogo corsi e percorsi formativi, pubblicazione quando vuoi.</span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Iscrizioni e accessi ordinati nel tuo spazio dedicato.</span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-check-circle mt-0.5 text-brand-amber" aria-hidden="true"></i><span>Progressi e report per rendicontazione.</span></li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4">
                <article class="welcome-section-card rounded-2xl border border-brand-blue/20 bg-slate-900/75 p-5 shadow-lg shadow-black/25 ring-1 ring-white/[0.04] backdrop-blur-sm sm:p-6">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-violet-400/35 bg-violet-500/15 text-violet-200">
                        <i class="ph ph-rocket-launch text-2xl" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-base font-semibold text-white">Attivazione in autonomia</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">La tua organizzazione si attiva dalla piattaforma, con abbonamento e dominio dedicato — ideale per reparti HR o segreterie che devono partire in fretta.</p>
                </article>
                <article class="welcome-section-card rounded-2xl border border-brand-blue/20 bg-slate-900/75 p-5 shadow-lg shadow-black/25 ring-1 ring-white/[0.04] backdrop-blur-sm sm:p-6">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-brand-blue/35 bg-brand-blue/15 text-white/90">
                        <i class="ph ph-books text-2xl" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-base font-semibold text-white">Corsi che costruisci tu</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">Moduli e lezioni con video HLS, SCORM e documenti: cataloghi per enti, percorsi interni per aziende — pubblichi quando sei pronto.</p>
                </article>
                <article class="welcome-section-card rounded-2xl border border-brand-blue/20 bg-slate-900/75 p-5 shadow-lg shadow-black/25 ring-1 ring-white/[0.04] backdrop-blur-sm sm:p-6">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-brand-navy/35 bg-brand-navy/15 text-white/90">
                        <i class="ph ph-student text-2xl" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-base font-semibold text-white">Partecipanti sotto controllo</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">Utenti creati dall’admin (anche via CSV): dipendenti o corsisti accedono a catalogo, iscrizioni e lezioni nel loro spazio — tu decidi chi fa cosa.</p>
                </article>
                <article class="welcome-section-card rounded-2xl border border-brand-blue/20 bg-slate-900/75 p-5 shadow-lg shadow-black/25 ring-1 ring-white/[0.04] backdrop-blur-sm sm:col-span-2 sm:p-6 xl:col-span-1">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-brand-amber/45 bg-brand-amber/15 text-white/90">
                        <i class="ph ph-chart-line-up text-2xl" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-base font-semibold text-white">Progressi e report</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">Completamenti e avanzamento sui contenuti: utile per audit in azienda e per rendicontazione negli enti — dall’area di gestione una visione chiara sull’adozione.</p>
                </article>
            </div>

            <p class="welcome-footnote mx-auto mt-8 max-w-2xl px-1 text-center text-xs leading-relaxed text-slate-400 sm:mt-10 sm:text-sm">
                <strong class="font-medium text-slate-300">Isolamento dati:</strong> un database dedicato per organizzazione (azienda o ente). Accesso learner e staff sul dominio della tua organizzazione (sottodominio o dominio proprio sui piani che lo includono).
            </p>
        </div>
    </section>

    {{-- Prezzi --}}
    <section id="prezzi" class="relative z-10 scroll-mt-28 py-14 md:py-24">
        <div class="mx-auto max-w-[1200px] px-4 sm:px-6">
            <div class="mx-auto mb-10 max-w-2xl text-center md:mb-12">
                <h2 class="text-xl font-bold tracking-tight text-white sm:text-2xl md:text-3xl">Piani e prezzi</h2>
                <p class="mt-3 text-sm text-slate-300 sm:text-base">Stessi piani per aziende ed enti: scegli mensile o annuale quando registri l’organizzazione. Prezzi IVA esclusa.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
                @foreach ($plans as $key => $meta)
                    @php
                        $label = $meta['label'] ?? ucfirst($key);
                        $contactEmail = (string) config('mail.from.address');
                        $contactEmail = (string) config('legal.email', $contactEmail);
                        $contactHref = 'https://mail.google.com/mail/?view=cm&fs=1&to='.rawurlencode($contactEmail).'&su='.rawurlencode('Richiesta piano Enterprise — '.config('app.name', 'FormaGrid'));
                        $pm = (int) ($meta['price_monthly_eur'] ?? 0);
                        $py = (int) ($meta['price_yearly_eur'] ?? 0);
                        $trialDays = (int) ($meta['trial_days'] ?? 0);
                        $cCourses = ($meta['courses'] ?? 0) === -1 ? 'Illimitati' : (string) ($meta['courses'] ?? '—');
                        $lMax = (int) ($meta['learners_max'] ?? 0);
                        $cLearners = $lMax === -1 ? 'Illimitati' : (string) $lMax;
                        $storageGb = (int) ($meta['storage_gb'] ?? 0);
                        $domainNote = ! empty($meta['custom_domain']) ? 'Dominio personalizzato' : 'Sottodominio incluso';
                        $isPro = $key === 'pro';
                        $isEnterprise = $key === 'enterprise';
                        $isBasic = $key === 'basic';
                        $yearlySavings = ($pm > 0 && $py > 0) ? max(0, ($pm * 12) - $py) : 0;
                    @endphp
                    <div class="welcome-pricing-card relative flex min-h-0 flex-col rounded-2xl border p-5 sm:p-6 {{ $isPro ? 'welcome-pricing-card--pro border-brand-blue/40 bg-slate-900/75 shadow-xl shadow-black/20 ring-1 ring-brand-blue/20 md:scale-[1.02] lg:scale-105' : 'border-white/15 bg-slate-900/70 ring-1 ring-white/[0.06]' }}">
                        @if ($isPro)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full border border-brand-blue/45 bg-brand-blue/20 px-3 py-0.5 text-xs font-semibold text-white/90">Consigliato</span>
                        @endif
                        @if ($isBasic && $trialDays > 0)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full border border-brand-amber/35 bg-brand-amber/15 px-3 py-0.5 text-xs font-semibold text-brand-amber">Prova {{ $trialDays }} giorni</span>
                        @endif
                        <h3 class="text-lg font-bold text-white">{{ $label }}</h3>
                        <div class="mt-4 flex flex-col gap-1">
                            @if ($pm > 0)
                                <p class="price-amount text-3xl font-bold tabular-nums text-white">{{ $pm }} €<span class="price-muted text-base font-normal text-slate-400">/mese</span></p>
                            @elseif ($isEnterprise)
                                <p class="price-amount text-3xl font-bold tabular-nums text-white">Su richiesta</p>
                                <p class="text-sm text-slate-300">A partire da <span class="font-semibold text-slate-100">499 €</span>/mese</p>
                            @endif
                            @if ($py > 0)
                                <p class="text-sm text-slate-300">oppure <span class="font-semibold text-slate-100">{{ $py }} €</span>/anno </p>
                                @if ($yearlySavings > 0)
                                    <p class="text-xs font-semibold text-brand-amber">Risparmi {{ $yearlySavings }} €/anno con l’annuale</p>
                                @endif
                            @endif
                        </div>
                        @if ($isBasic && $trialDays > 0)
                            <p class="mt-2 text-xs text-slate-400">Include prova gratuita di {{ $trialDays }} giorni.</p>
                        @endif
                        <ul class="price-list mt-5 flex flex-1 flex-col gap-2.5 text-sm text-slate-200">
                            <li class="flex items-start gap-2">
                                <i class="ph ph-check-circle mt-0.5 shrink-0 text-brand-amber" aria-hidden="true"></i>
                                <span><strong class="text-white">{{ $cCourses }}</strong> corsi</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="ph ph-check-circle mt-0.5 shrink-0 text-brand-amber" aria-hidden="true"></i>
                                <span>
                                    @if ($lMax === -1)
                                        <strong class="text-white">Partecipanti illimitati</strong>
                                    @else
                                        Fino a <strong class="text-white">{{ $cLearners }}</strong> partecipanti attivi
                                    @endif
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="ph ph-check-circle mt-0.5 shrink-0 text-brand-amber" aria-hidden="true"></i>
                                <span><strong class="text-white">{{ $storageGb }} GB</strong> storage media</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="ph ph-check-circle mt-0.5 shrink-0 text-brand-amber" aria-hidden="true"></i>
                                <span>{{ $domainNote }}</span>
                            </li>
                        </ul>
                        @if ($isEnterprise)
                            <a href="{{ $contactHref }}"
                               class="mt-6 inline-flex min-h-[48px] w-full items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15 active:scale-95">
                                Contattaci
                            </a>
                        @else
                            <a href="{{ route('central.register') }}"
                               class="mt-6 inline-flex min-h-[48px] w-full items-center justify-center rounded-xl bg-brand-blue px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                                Scegli {{ $label }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <details class="mx-auto mt-10 max-w-5xl rounded-2xl border border-white/10 bg-slate-900/45 p-5 ring-1 ring-white/[0.04] sm:p-6">
                <summary class="cursor-pointer list-none select-none text-sm font-semibold text-white">
                    <span class="inline-flex items-center gap-2">
                        <i class="ph ph-list-checks" aria-hidden="true"></i>
                        Confronta i piani (Basic vs Pro vs Enterprise)
                        <span class="ml-1 text-xs font-medium text-slate-400">(clicca per aprire)</span>
                    </span>
                </summary>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-[720px] w-full overflow-hidden rounded-xl border border-white/10 bg-white/70 shadow-sm shadow-slate-300/20 dark:bg-slate-950/30 dark:shadow-black/30">
                        <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            <th class="sticky left-0 z-10 bg-slate-50/90 px-4 py-3 backdrop-blur dark:bg-slate-950/55">Funzione</th>
                            <th class="bg-slate-50/90 px-4 py-3 backdrop-blur dark:bg-slate-950/55">Basic</th>
                            <th class="bg-brand-blue/10 px-4 py-3 text-brand-navy dark:bg-brand-blue/15 dark:text-white">Pro</th>
                            <th class="bg-slate-50/90 px-4 py-3 backdrop-blur dark:bg-slate-950/55">Enterprise</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 text-sm text-slate-700 dark:divide-white/10 dark:text-slate-200">
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">SCORM & media (video/documenti)</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Report progressi</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Gestione utenti via CSV</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Accesso API</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400"><i class="ph ph-minus-circle" aria-hidden="true"></i>No</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span> <span class="text-xs text-slate-500 dark:text-slate-400">(estendibile)</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Export dati avanzato <span class="block text-xs font-normal text-slate-500 dark:text-slate-400">(bulk, integrazioni)</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400"><i class="ph ph-minus-circle" aria-hidden="true"></i>Report standard</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span> <span class="text-xs text-slate-500 dark:text-slate-400">+ dedicato</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Dominio personalizzato</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400"><i class="ph ph-minus-circle" aria-hidden="true"></i>No</span></td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 font-medium"><i class="ph ph-check-circle text-brand-amber" aria-hidden="true"></i>Sì</span></td>
                        </tr>
                        <tr class="odd:bg-white/70 even:bg-slate-50/80 hover:bg-slate-100/70 dark:odd:bg-white/[0.02] dark:even:bg-white/[0.04] dark:hover:bg-white/[0.06]">
                            <td class="sticky left-0 z-10 bg-white/85 px-4 py-3 font-medium text-slate-700 backdrop-blur dark:bg-slate-950/45 dark:text-slate-200">Supporto</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">Standard</td>
                            <td class="bg-brand-blue/[0.04] px-4 py-3 text-slate-700 dark:text-slate-200">Prioritario</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">Dedicato</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="keep-white-in-light relative z-10 border-t border-white/5 bg-gradient-to-b from-slate-950 to-slate-900 py-12 md:py-16">
        <div class="mx-auto max-w-[1200px] px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-xl font-bold tracking-tight text-white sm:text-2xl md:text-3xl">Domande frequenti</h2>
                <p class="mt-3 text-sm text-slate-300 sm:text-base">Le risposte alle domande che di solito bloccano la decisione.</p>
            </div>
            <div class="mx-auto mt-8 max-w-3xl space-y-3">
                <details class="group rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-5 ring-1 ring-white/[0.04] backdrop-blur-sm">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-white">
                        <span class="inline-flex items-center gap-2"><i class="ph ph-question" aria-hidden="true"></i>Cosa succede dopo i 14 giorni?</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        Durante la prova puoi configurare lo spazio e caricare contenuti. Alla fine del periodo scegli un piano e completi il pagamento su Stripe per continuare senza interruzioni.
                    </p>
                </details>
                <details class="group rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-5 ring-1 ring-white/[0.04] backdrop-blur-sm">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-white">
                        <span class="inline-flex items-center gap-2"><i class="ph ph-question" aria-hidden="true"></i>Posso caricare SCORM 2004?</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        Puoi caricare pacchetti SCORM. Se hai requisiti specifici su edizione/compatibilità (es. 2004 4th ed.), scrivici: ti diciamo cosa è supportato nel tuo caso e come validarlo nel tuo ambiente.
                    </p>
                </details>
                <details class="group rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-5 ring-1 ring-white/[0.04] backdrop-blur-sm">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-white">
                        <span class="inline-flex items-center gap-2"><i class="ph ph-question" aria-hidden="true"></i>Posso esportare i dati?</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        Sì: report e dati operativi possono essere esportati. Se ti serve un export strutturato per integrazioni o migrazione, il piano Enterprise include supporto dedicato.
                    </p>
                </details>
                <details class="group rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-5 ring-1 ring-white/[0.04] backdrop-blur-sm">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-white">
                        <span class="inline-flex items-center gap-2"><i class="ph ph-question" aria-hidden="true"></i>Cosa succede ai dati se cancello l’account?</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        Alla chiusura dell’abbonamento lo spazio dell’organizzazione viene disattivato secondo i termini contrattuali. I dati vengono conservati solo per il tempo necessario a obblighi di legge o contestazioni, poi eliminati o anonimizzati. Per dettagli operativi e tempistiche, consulta la Privacy o scrivi a <a class="font-semibold text-brand-amber underline decoration-brand-amber/40 underline-offset-4 hover:text-brand-amber/90" href="mailto:{{ config('legal.privacy_email') }}">{{ config('legal.privacy_email') }}</a>.
                    </p>
                </details>
                <details class="group rounded-2xl border border-[rgba(255,255,255,0.10)] bg-[rgba(15,23,42,0.55)] p-5 ring-1 ring-white/[0.04] backdrop-blur-sm">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-white">
                        <span class="inline-flex items-center gap-2"><i class="ph ph-question" aria-hidden="true"></i>Supportate SSO / LDAP?</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        SSO enterprise (SAML/OIDC) e integrazioni LDAP possono essere attivati su richiesta (piani Pro ed Enterprise). Scrivici con i tuoi requisiti: ti indichiamo tempi e modalità di attivazione.
                    </p>
                </details>
            </div>
        </div>
    </section>

    {{-- Chiusura --}}
    <section class="keep-white-in-light relative z-10 border-t border-slate-200 bg-gradient-to-b from-white to-slate-50 py-12 md:py-16 dark:border-white/10 dark:from-slate-950 dark:to-slate-900">
        <div class="mx-auto max-w-[1200px] px-4 text-center sm:px-6">
            <p class="welcome-closing-title text-lg font-semibold text-slate-900 dark:text-white">La tua organizzazione è a un clic dalla formazione</p>
            <p class="welcome-closing-text mt-2 text-sm text-slate-600 dark:text-slate-300">Registra l’organizzazione in autonomia e completa il pagamento sicuro con Stripe.</p>
            <a href="{{ route('central.register') }}"
               class="mt-6 inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-brand-blue px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                Inizia subito <i class="ph ph-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</x-layouts.central>
