<x-layouts.tenant :title="'Profilo organizzazione — Admin'">
    <div class="mx-auto max-w-[840px] px-6 py-10">
        @if (session('toast'))
            <div class="admin-toast-success mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm">
                {{ session('toast') }}
            </div>
        @endif

        <div class="mb-10">
            <h1 class="text-2xl font-bold tracking-tight text-white">Impostazioni organizzazione</h1>
            <p class="mt-2 text-xs text-slate-500">
                Slug identificativo: <span class="font-mono text-slate-400">{{ tenant('id') }}</span>
            </p>
        </div>

        <div class="space-y-10">
            <section class="glass-panel rounded-2xl p-8">
                <h2 class="text-lg font-semibold text-white">Organizzazione</h2>
                <p class="mt-1 text-sm text-slate-400">Il nome compare nelle comunicazioni agli utenti. L'email di contatto è usata per notifiche amministrative.</p>

                <form method="post" action="{{ route('tenant.admin.profile.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <label class="form-label" for="organization_name">Nome organizzazione</label>
                        <input id="organization_name" name="organization_name" class="form-input"
                               value="{{ $organizationName }}" placeholder="es. Azienda S.p.A.">
                        @error('organization_name') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="contact_email">Email di contatto</label>
                        <input id="contact_email" name="contact_email" type="email" class="form-input"
                               value="{{ $contactEmail }}" placeholder="amministrazione@esempio.it">
                        @error('contact_email') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-floppy-disk text-lg"></i>
                        Salva dati organizzazione
                    </button>
                </form>
            </section>

            <section class="glass-panel rounded-2xl p-8">
                <h2 class="text-lg font-semibold text-white">PDF resoconto corsi</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Personalizza intestazione, colore e footer del PDF “ore del corso” esportabile dall’area corsi.
                </p>

                <form method="post" action="{{ route('tenant.admin.profile.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <label class="form-label" for="pdf_report_accent">Colore accento (hex)</label>
                        <input id="pdf_report_accent" name="pdf_report_accent" class="form-input"
                               value="{{ $pdfReportAccent }}" placeholder="#f59e0b">
                        <p class="mt-2 text-xs text-slate-500">Formato: <span class="font-mono">#RRGGBB</span>. Se vuoto, usa il default.</p>
                        @error('pdf_report_accent') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="pdf_report_header">Intestazione (testo)</label>
                        <textarea id="pdf_report_header" name="pdf_report_header" rows="4" class="form-input"
                                  placeholder="Esempio: Resoconto ore corso — valido ai fini della formazione interna.">{{ $pdfReportHeader }}</textarea>
                        @error('pdf_report_header') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="pdf_report_footer">Footer (testo)</label>
                        <textarea id="pdf_report_footer" name="pdf_report_footer" rows="3" class="form-input"
                                  placeholder="Esempio: Documento generato automaticamente dalla piattaforma.">{{ $pdfReportFooter }}</textarea>
                        @error('pdf_report_footer') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-floppy-disk text-lg"></i>
                        Salva impostazioni PDF
                    </button>
                </form>
            </section>

            <section class="glass-panel rounded-2xl p-8">
                <h2 class="text-lg font-semibold text-white">Brand e logo</h2>
                <p class="mt-1 text-sm text-slate-400">Compare nell'intestazione per tutti gli utenti della tua organizzazione.</p>

                <div class="mt-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-brand-blue/30 bg-gradient-to-br from-brand-blue/15 to-brand-navy/15">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo organizzazione" class="max-h-14 max-w-14 object-contain">
                        @else
                            <img src="{{ asset('brand/formagrid-logo.svg') }}" alt="Logo organizzazione" class="h-10 w-10 object-contain opacity-80">
                        @endif
                    </div>
                    <div class="text-sm text-slate-400">
                        @if ($logoUrl)
                            <span class="font-medium text-slate-200">Logo personalizzato attivo.</span>
                        @else
                            In uso il simbolo FormaGrid predefinito.
                        @endif
                    </div>
                </div>

                <form method="post" action="{{ route('tenant.admin.profile.logo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <label class="form-label" for="logo">Carica nuovo logo</label>
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                               class="block w-full cursor-pointer rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-md file:border-0 file:bg-brand-blue/15 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white/90 hover:file:bg-brand-blue/25">
                        <p class="mt-2 text-xs text-slate-500">PNG, JPG, GIF, WebP o SVG. Massimo 2 MB.</p>
                        @error('logo') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-upload-simple text-lg"></i>
                        Salva logo
                    </button>
                </form>

                @if ($logoUrl)
                    <form method="post" action="{{ route('tenant.admin.profile.logo.update') }}" class="mt-6 border-t border-white/10 pt-6">
                        @csrf
                        @method('put')
                        <input type="hidden" name="remove_logo" value="1">
                        <button type="submit" class="admin-btn-secondary inline-flex items-center gap-2 border-rose-400/40 text-rose-200 hover:border-rose-400/60 hover:bg-rose-500/10">
                            <i class="ph ph-trash text-lg"></i>
                            Ripristina logo predefinito
                        </button>
                    </form>
                @endif
            </section>

            <section class="glass-panel rounded-2xl p-8">
                <h2 class="text-lg font-semibold text-white">Dominio personalizzato</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Puoi aggiungere un dominio del cliente (es. <span class="font-mono text-slate-300">academy.cliente.it</span>) oltre al sottodominio predefinito.
                    Requisiti: DNS che punta al VPS + certificato SSL valido su quel dominio (HTTPS obbligatorio).
                </p>

                @error('custom_domain') <div class="mt-4 text-sm text-rose-300">{{ $message }}</div> @enderror
                @if (session('domain_check'))
                    @php
                        $check = session('domain_check');
                    @endphp
                    <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Verifica dominio: <span class="font-mono text-slate-300">{{ $check['domain'] ?? '' }}</span></div>
                        <div class="mt-3 space-y-2 text-slate-300">
                            <div>
                                <span class="font-semibold {{ data_get($check, 'dns.ok') ? 'text-emerald-200' : 'text-rose-200' }}">DNS</span>
                                <div class="mt-1 text-xs text-slate-400">{{ data_get($check, 'dns.details') }}</div>
                            </div>
                            @if (data_get($check, 'provision.attempted'))
                                <div>
                                    <span class="font-semibold {{ data_get($check, 'provision.ok') ? 'text-emerald-200' : 'text-rose-200' }}">SSL</span>
                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ data_get($check, 'provision.ok') ? 'Provisioning SSL completato.' : 'Provisioning SSL fallito.' }}
                                        @if (data_get($check, 'provision.details') && ! data_get($check, 'provision.ok'))
                                            <span class="block mt-1 font-mono text-slate-500">{{ data_get($check, 'provision.details') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div>
                                <span class="font-semibold {{ data_get($check, 'http.ok') ? 'text-emerald-200' : 'text-rose-200' }}">HTTP/HTTPS</span>
                                <div class="mt-1 text-xs text-slate-400">{{ data_get($check, 'http.details') }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Domini associati</div>
                        <div class="text-xs text-slate-500">
                            Predefinito: <span class="font-mono text-slate-400">{{ tenant('id') }}.{{ $centralDomain }}</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($domains as $d)
                            @php
                                $defaultDomain = tenant('id').'.'.$centralDomain;
                                $isDefault = ($d->domain === $defaultDomain);
                            @endphp

                            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="min-w-0 font-mono text-slate-200">{{ $d->domain }}</div>
                                            @if ($isDefault)
                                                <span class="rounded-full border border-brand-blue/25 bg-brand-blue/10 px-2 py-0.5 text-[11px] font-semibold tracking-wide text-brand-blue">
                                                    Predefinito
                                                </span>
                                            @else
                                                <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] font-semibold tracking-wide text-slate-300">
                                                    Custom
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            HTTPS obbligatorio. Se il DNS è pronto, “Verifica” attiva automaticamente SSL.
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                                        <a href="https://{{ $d->domain }}" target="_blank" rel="noreferrer"
                                           class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-1.5 text-xs">
                                            <i class="ph ph-arrow-square-out text-base"></i>
                                            Apri
                                        </a>
                                        <form method="post" action="{{ route('tenant.admin.profile.custom-domain.check', ['domain' => $d->domain]) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-1.5 text-xs">
                                                <i class="ph ph-plug text-base"></i>
                                                Verifica
                                            </button>
                                        </form>

                                        @if (! $isDefault)
                                            <form method="post" action="{{ route('tenant.admin.profile.custom-domain.remove', ['domain' => $d->domain]) }}">
                                                @csrf
                                                @method('delete')
                                                <button type="submit"
                                                        class="admin-btn-secondary inline-flex items-center gap-2 border-rose-400/40 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400/60 hover:bg-rose-500/10">
                                                    <i class="ph ph-trash text-base"></i>
                                                    Rimuovi
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-white/10 bg-white/5 px-4 py-5 text-center text-sm text-slate-500">
                                Nessun dominio configurato.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6">
                    @if (! $allowsCustomDomain)
                        <div class="rounded-xl border border-amber-300/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                            Il tuo piano non include il dominio personalizzato.
                        </div>
                    @else
                        <div class="mb-4 rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/0 px-5 py-4 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Istruzioni per il dominio personalizzato</div>
                                    <div class="mt-1 text-xs text-slate-400">
                                        Il cliente deve solo puntare il DNS. L’HTTPS viene attivato automaticamente.
                                    </div>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-400">
                                    HTTPS richiesto
                                </div>
                            </div>
                            <ul class="mt-4 space-y-2 text-xs text-slate-300">
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-300">1</span>
                                    <span>
                                        DNS: crea un record <span class="font-mono">CNAME</span> che punta a:
                                        <span class="font-mono text-slate-200">{{ tenant('id') }}.{{ $centralDomain }}</span>
                                    </span>
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-300">2</span>
                                    <span>Attendi la propagazione DNS (minuti/ore).</span>
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-300">3</span>
                                    <span>
                                        Torna qui e premi <span class="font-semibold text-slate-100">“Verifica”</span>: se DNS è ok, il certificato SSL verrà emesso automaticamente.
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <form method="post" action="{{ route('tenant.admin.profile.custom-domain.add') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="form-label" for="custom_domain">Aggiungi dominio</label>
                                <input id="custom_domain" name="custom_domain" class="form-input"
                                       value="{{ old('custom_domain') }}"
                                       placeholder="academy.cliente.it">
                                <p class="mt-2 text-xs text-slate-500">
                                    Inserisci solo l’host (niente <span class="font-mono">https://</span>, niente slash).
                                </p>
                            </div>
                            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2">
                                <i class="ph ph-plus text-lg"></i>
                                Aggiungi dominio
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-layouts.tenant>
