<x-layouts.tenant :title="'Profilo organizzazione — Admin'">
    <x-ui.page>
        <x-ui.page-header
            title="Impostazioni organizzazione"
            :subtitle="'Slug identificativo: '.tenant('id')"
        />

        <section class="border-b border-base-300 px-4 py-6 lg:px-6">
            <div class="mx-auto max-w-3xl">
                <h2 class="text-lg font-semibold">Organizzazione</h2>

                <form method="post" action="{{ route('tenant.admin.profile.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div class="form-control w-full">
                        <label class="label" for="organization_name">
                            <span class="label-text">Nome organizzazione</span>
                        </label>
                        <input id="organization_name" class="input input-bordered w-full opacity-80" value="{{ $organizationName }}" readonly>
                    </div>

                    <div class="form-control w-full">
                        <label class="label" for="contact_email">
                            <span class="label-text">Email di contatto</span>
                        </label>
                        <input id="contact_email" name="contact_email" type="email" class="input input-bordered w-full"
                               value="{{ $contactEmail }}" placeholder="amministrazione@esempio.it">
                        @error('contact_email') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-floppy-disk text-lg"></i>
                        Salva dati organizzazione
                    </button>
                </form>
            </div>
        </section>

        <section class="border-b border-base-300 px-4 py-6 lg:px-6">
            <div class="mx-auto max-w-3xl">
                <h2 class="text-lg font-semibold">PDF resoconto corsi</h2>
                <p class="mt-1 text-sm text-base-content/65">
                    Queste impostazioni modificano l'aspetto del PDF "ore del corso" esportabile dall'area corsi.
                </p>

                @php
                    $defaultPdfAccent = (string) config('branding.accent', '#1a6dbf');
                    $initialAccent = trim((string) ($pdfReportAccent ?? ''));
                    $initialAccent = $initialAccent !== '' ? $initialAccent : $defaultPdfAccent;
                @endphp

                <div class="mt-6 grid gap-8 lg:grid-cols-2">
                    <form method="post" action="{{ route('tenant.admin.profile.update') }}" class="space-y-6">
                        @csrf
                        @method('put')

                        <div class="form-control w-full">
                            <label class="label" for="pdf_report_accent_hex">
                                <span class="label-text">Colore accento</span>
                            </label>
                            <div class="mt-2 flex items-center gap-3">
                                <input id="pdf_report_accent_picker" type="color"
                                       class="h-10 w-12 cursor-pointer border border-base-300 bg-base-200 p-1"
                                       value="{{ $initialAccent }}"
                                       aria-label="Selettore colore">
                                <div class="flex-1">
                                    <input id="pdf_report_accent_hex" name="pdf_report_accent" class="input input-bordered w-full font-mono"
                                           value="{{ $pdfReportAccent }}" placeholder="{{ $defaultPdfAccent }}"
                                           inputmode="text" autocomplete="off" spellcheck="false">
                                    <p class="mt-2 text-xs text-base-content/60">
                                        Inserisci un colore <span class="font-mono">#RRGGBB</span> oppure usa il selettore. Se lasci vuoto, viene usato il default.
                                    </p>
                                </div>
                                <button type="button" id="pdf_report_accent_reset"
                                        class="btn btn-outline btn-sm inline-flex items-center gap-2">
                                    <i class="ph ph-arrow-counter-clockwise text-base"></i>
                                    Default
                                </button>
                            </div>
                            @error('pdf_report_accent') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="pdf_report_header">
                                <span class="label-text">Intestazione</span>
                            </label>
                            <textarea id="pdf_report_header" name="pdf_report_header" rows="4" class="textarea textarea-bordered w-full"
                                      placeholder="Esempio: Resoconto ore corso — valido ai fini della formazione interna.">{{ $pdfReportHeader }}</textarea>
                            <p class="mt-2 text-xs text-base-content/60">Suggerimento: usa 1–2 righe brevi. Puoi lasciare vuoto.</p>
                            @error('pdf_report_header') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label" for="pdf_report_footer">
                                <span class="label-text">Footer</span>
                            </label>
                            <textarea id="pdf_report_footer" name="pdf_report_footer" rows="3" class="textarea textarea-bordered w-full"
                                      placeholder="Esempio: Documento generato automaticamente dalla piattaforma.">{{ $pdfReportFooter }}</textarea>
                            <p class="mt-2 text-xs text-base-content/60">Comparirà in fondo ad ogni pagina del PDF. Puoi lasciare vuoto.</p>
                            @error('pdf_report_footer') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary inline-flex items-center gap-2">
                            <i class="ph ph-floppy-disk text-lg"></i>
                            Salva impostazioni PDF
                        </button>
                    </form>

                    <div class="border border-base-300 p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Anteprima (esempio)</div>
                            <div class="text-xs text-base-content/60">
                                Colore: <span id="pdf_preview_accent_label" class="font-mono">{{ $initialAccent }}</span>
                            </div>
                        </div>

                        <div id="pdf_preview"
                             class="mt-4 overflow-hidden border border-base-300 bg-white text-slate-900"
                             data-default-accent="{{ $defaultPdfAccent }}"
                             style="--accent: {{ $initialAccent }};">
                            <div class="h-2 w-full" style="background: var(--accent);"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--accent);">
                                            Resoconto ore corso
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-slate-900">
                                            <span class="text-slate-500">Organizzazione:</span> {{ tenant('organization_name') }}
                                        </div>
                                    </div>
                                    <div class="border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        PDF
                                    </div>
                                </div>

                                <div class="mt-4 border border-slate-200 bg-slate-50 p-3">
                                    <div class="text-[11px] font-semibold text-slate-500">Intestazione</div>
                                    <div id="pdf_preview_header" class="mt-1 whitespace-pre-wrap text-sm text-slate-800">
                                        {{ trim((string) $pdfReportHeader) !== '' ? $pdfReportHeader : '— (vuoto) —' }}
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <div class="border border-slate-200 bg-white p-3">
                                        <div class="text-[11px] font-semibold text-slate-500">Ore</div>
                                        <div class="mt-1 text-lg font-bold text-slate-900">12,5</div>
                                    </div>
                                    <div class="border border-slate-200 bg-white p-3">
                                        <div class="text-[11px] font-semibold text-slate-500">Aziende</div>
                                        <div class="mt-1 text-lg font-bold text-slate-900">4</div>
                                    </div>
                                    <div class="border border-slate-200 bg-white p-3">
                                        <div class="text-[11px] font-semibold text-slate-500">Allievi</div>
                                        <div class="mt-1 text-lg font-bold text-slate-900">27</div>
                                    </div>
                                </div>

                                <div class="mt-5 border-t border-slate-200 pt-3 text-[11px] text-slate-500">
                                    <div class="font-semibold text-slate-600">Footer</div>
                                    <div id="pdf_preview_footer" class="mt-1 whitespace-pre-wrap">
                                        {{ trim((string) $pdfReportFooter) !== '' ? $pdfReportFooter : '— (vuoto) —' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-base-content/60">
                            Nota: è una simulazione. Il PDF reale userà queste impostazioni quando esporti il resoconto.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-base-300 px-4 py-6 lg:px-6">
            <div class="mx-auto max-w-3xl">
                <h2 class="text-lg font-semibold">Brand e logo</h2>
                <p class="mt-1 text-sm text-base-content/65">Compare nell'intestazione per tutti gli utenti della tua organizzazione.</p>

                <div class="mt-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden border border-primary/30 bg-primary/10">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo organizzazione" class="max-h-14 max-w-14 object-contain">
                        @else
                            <img src="{{ asset('brand/formagrid-logo.svg') }}" alt="Logo organizzazione" class="h-10 w-10 object-contain opacity-80">
                        @endif
                    </div>
                    <div class="text-sm text-base-content/70">
                        @if ($logoUrl)
                            <span class="font-medium">Logo personalizzato attivo.</span>
                        @else
                            In uso il simbolo FormaGrid predefinito.
                        @endif
                    </div>
                </div>

                <form method="post" action="{{ route('tenant.admin.profile.logo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div class="form-control w-full">
                        <label class="label" for="logo">
                            <span class="label-text">Carica nuovo logo</span>
                        </label>
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                               class="file-input file-input-bordered w-full">
                        <p class="mt-2 text-xs text-base-content/60">PNG, JPG, GIF, WebP o SVG. Massimo 2 MB.</p>
                        @error('logo') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-upload-simple text-lg"></i>
                        Salva logo
                    </button>
                </form>

                @if ($logoUrl)
                    <form method="post" action="{{ route('tenant.admin.profile.logo.update') }}" class="mt-6 border-t border-base-300 pt-6">
                        @csrf
                        @method('put')
                        <input type="hidden" name="remove_logo" value="1">
                        <button type="submit" class="btn btn-error btn-outline inline-flex items-center gap-2">
                            <i class="ph ph-trash text-lg"></i>
                            Ripristina logo predefinito
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </x-ui.page>

    <script>
        (() => {
            const init = () => {
                const hex = document.getElementById('pdf_report_accent_hex');
                const picker = document.getElementById('pdf_report_accent_picker');
                const reset = document.getElementById('pdf_report_accent_reset');
                const header = document.getElementById('pdf_report_header');
                const footer = document.getElementById('pdf_report_footer');
                const preview = document.getElementById('pdf_preview');
                const previewAccentLabel = document.getElementById('pdf_preview_accent_label');
                const previewHeader = document.getElementById('pdf_preview_header');
                const previewFooter = document.getElementById('pdf_preview_footer');

                if (!hex || !picker || !reset || !preview || !previewAccentLabel || !previewHeader || !previewFooter || !header || !footer) {
                    return;
                }

                const normalizeHex = (value) => {
                    if (!value) return '';
                    const v = String(value).trim();
                    const m = v.match(/^#?[0-9a-fA-F]{6}$/);
                    if (!m) return v;
                    return ('#' + v.replace(/^#/, '')).toLowerCase();
                };

                const defaultAccent = (preview.dataset && preview.dataset.defaultAccent) ? String(preview.dataset.defaultAccent) : '#1a6dbf';

                const effectiveAccent = (value) => {
                    const v = normalizeHex(value);
                    return (v && /^#[0-9a-f]{6}$/i.test(v)) ? v : defaultAccent;
                };

                const render = () => {
                    const effective = effectiveAccent(hex.value);
                    preview.style.setProperty('--accent', effective);
                    picker.value = effective;
                    previewAccentLabel.textContent = effective;

                    const h = String(header.value || '').trim();
                    const f = String(footer.value || '').trim();
                    previewHeader.textContent = h !== '' ? h : '— (vuoto) —';
                    previewFooter.textContent = f !== '' ? f : '— (vuoto) —';
                };

                const onHexChange = () => {
                    const v = normalizeHex(hex.value);
                    if (/^#[0-9a-f]{6}$/i.test(v)) {
                        hex.value = v;
                    }
                    render();
                };

                hex.addEventListener('input', onHexChange);
                hex.addEventListener('change', onHexChange);
                hex.addEventListener('keyup', onHexChange);
                picker.addEventListener('input', () => {
                    hex.value = picker.value;
                    render();
                });
                reset.addEventListener('click', () => {
                    hex.value = '';
                    render();
                });
                header.addEventListener('input', render);
                header.addEventListener('keyup', render);
                footer.addEventListener('input', render);
                footer.addEventListener('keyup', render);

                render();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init, { once: true });
            } else {
                init();
            }
        })();
    </script>
</x-layouts.tenant>
