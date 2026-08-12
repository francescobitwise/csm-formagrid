<x-layouts.tenant :title="$module->title.' — Lezioni'">
    <div data-content-status-url="{{ route('tenant.admin.modules.lessons.content-status', $module) }}">
        <header class="border-b border-base-300 bg-base-100 px-4 py-5 lg:px-6">
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-base-content/50">
                <a href="{{ route('tenant.admin.modules.index') }}" class="link link-hover">Moduli</a>
                <i class="ph ph-caret-right text-[10px]" aria-hidden="true"></i>
                <span class="text-base-content/70">Lezioni</span>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight text-base-content sm:text-3xl">{{ $module->title }}</h1>
                    <p class="mt-1 max-w-2xl text-sm text-base-content/60">
                        Lezioni e contenuti del modulo. Per usarlo in un corso, associa il modulo dal builder del corso.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <p class="text-base-content/70">
                            <span class="text-base-content/45">Durata totale</span>
                            <span class="ml-1.5 font-mono font-semibold tabular-nums text-base-content">
                                @if (($moduleLessonDurationCount ?? 0) > 0)
                                    {{ \App\Support\DurationFormat::secondsToMmss($moduleTotalDurationSeconds ?? 0) }}
                                @else
                                    <span class="font-normal text-base-content/55">non indicata</span>
                                @endif
                            </span>
                        </p>
                        @if ($module->courses->isNotEmpty())
                            <p class="text-xs text-base-content/50">
                                Presente in
                                @foreach ($module->courses as $c)
                                    <a href="{{ route('tenant.admin.courses.builder', $c) }}" class="link link-hover font-medium text-base-content/70">{{ $c->title }}</a>@if (! $loop->last), @endif
                                @endforeach
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('tenant.admin.modules.edit', $module) }}" class="btn btn-outline btn-sm">
                        Impostazioni modulo
                    </a>
                    <a href="{{ route('tenant.admin.modules.index') }}" class="btn btn-ghost btn-sm">
                        Tutti i moduli
                    </a>
                </div>
            </div>
        </header>

        <div class="border-b border-base-300 bg-base-100 px-4 py-4 lg:px-6">
            <div
                class="lesson-dropzone"
                data-bulk-lesson-upload
                data-bulk-dropzone
                data-from-file-url="{{ route('tenant.admin.modules.lessons.from-file', $module) }}"
                data-presign-url="{{ route('api.video.presigned-upload') }}"
                data-finalize-url="{{ route('api.video.finalize-upload') }}"
                data-module-id="{{ $module->id }}"
                data-scorm-upload-url-template="{{ str_replace('00000000-0000-0000-0000-000000000000', '__LESSON__', route('tenant.admin.modules.lessons.scorm.upload', [$module, '00000000-0000-0000-0000-000000000000'])) }}"
            >
                <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-base-200/80 text-primary">
                        <i class="ph ph-upload-simple text-2xl" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-base-content">Carica contenuti</p>
                        <p class="mt-0.5 text-xs text-base-content/60">
                            Trascina o seleziona più file: video <span class="font-mono text-base-content/80">.mp4</span> / <span class="font-mono text-base-content/80">.m3u8</span>
                            o pacchetti SCORM <span class="font-mono text-base-content/80">.zip</span>.
                            Il titolo della lezione viene preso dal nome file.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        type="file"
                        data-bulk-file
                        multiple
                        accept=".mp4,.m3u8,.zip,video/mp4,application/vnd.apple.mpegurl,application/x-mpegURL,application/zip,application/x-zip-compressed"
                        class="file-input file-input-bordered file-input-sm min-w-0 flex-1 bg-base-100"
                        aria-label="Seleziona video o pacchetti SCORM"
                    >
                    <button type="button" data-bulk-submit class="btn btn-primary btn-sm shrink-0" disabled>
                        <i class="ph ph-cloud-arrow-up" aria-hidden="true"></i>
                        <span class="ml-1">Carica selezionati</span>
                    </button>
                </div>

                <ul class="mt-3 divide-y divide-base-300/40 empty:hidden" data-bulk-queue aria-live="polite"></ul>
                <p class="mt-2 min-h-[1rem] text-xs text-base-content/60" data-bulk-summary></p>
            </div>
        </div>

        <div class="border-b border-base-300 bg-base-100">
            @if ($module->lessons->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-base-content/55 lg:px-6">
                    Nessuna lezione ancora. Aggiungi la prima qui sotto.
                </p>
            @else
                <div class="hidden items-center gap-3 border-b border-base-300/60 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-base-content/55 sm:flex lg:px-6">
                    <span class="w-6 shrink-0 text-center">#</span>
                    <span class="min-w-0 flex-1">Lezione</span>
                    <span class="w-24 shrink-0">Tipo</span>
                    <span class="w-28 shrink-0">Stato</span>
                    <span class="w-14 shrink-0 text-right">Durata</span>
                    <span class="w-[5.5rem] shrink-0 text-right">Azioni</span>
                </div>
                <div class="lesson-list divide-y divide-base-300/50">
                    @foreach ($module->lessons as $lesson)
                        @include('tenant.admin.modules.partials.lesson-row')
                    @endforeach
                </div>
            @endif

            <div class="border-t border-base-300/60 bg-base-200/40 px-4 py-2 lg:px-6">
                <form method="post" action="{{ route('tenant.admin.modules.lessons.store', [$module]) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2">
                    @csrf
                    <span class="hidden w-6 shrink-0 text-center font-mono text-xs text-base-content/35 sm:inline" aria-hidden="true">+</span>
                    <input id="new_lesson_title" name="title" class="input input-bordered input-sm min-w-0 flex-1 bg-base-100" placeholder="Nuova lezione…" required minlength="2" value="{{ old('title') }}" aria-label="Titolo nuova lezione">
                    <label class="sr-only" for="new_lesson_type">Tipo</label>
                    <select id="new_lesson_type" name="type" class="select select-bordered select-sm w-full bg-base-100 text-sm sm:w-32" aria-label="Tipo lezione">
                        @foreach ($lessonTypes as $t)
                            @php
                                $label = match ($t->value) {
                                    'video' => 'Video',
                                    'scorm' => 'SCORM',
                                    'document' => 'Documento',
                                    default => $t->value,
                                };
                            @endphp
                            <option value="{{ $t->value }}" @selected(old('type', 'document') === $t->value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input id="new_lesson_duration_minutes" name="duration_minutes" value="{{ old('duration_minutes') }}"
                           class="input input-bordered input-sm w-full bg-base-100 font-mono sm:w-16" placeholder="Min" inputmode="numeric" autocomplete="off"
                           min="0" step="1" aria-label="Minuti (durata opzionale)" data-new-lesson-duration>
                    <input id="new_lesson_duration_seconds" name="duration_seconds" value="{{ old('duration_seconds') }}"
                           class="input input-bordered input-sm w-full bg-base-100 font-mono sm:w-16" placeholder="Sec" inputmode="numeric" autocomplete="off"
                           min="0" max="59" step="1" aria-label="Secondi (durata opzionale, 0–59)" data-new-lesson-duration>
                    <label class="flex shrink-0 items-center gap-1.5 text-xs text-base-content/70" title="Lezione richiesta">
                        <input type="hidden" name="is_required" value="0">
                        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', '1') === '1') class="checkbox checkbox-primary checkbox-sm shrink-0">
                        <span class="hidden sm:inline">Richiesta</span>
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm shrink-0">
                        <i class="ph ph-plus" aria-hidden="true"></i>
                        <span class="ml-1">Aggiungi</span>
                    </button>
                </form>
            </div>
        </div>

        <div
            id="lesson-drawer-backdrop"
            class="lesson-drawer-backdrop"
            data-lesson-close
            hidden
            aria-hidden="true"
        ></div>
        <aside
            id="lesson-drawer"
            class="lesson-drawer"
            role="dialog"
            aria-modal="true"
            aria-label="Gestione lezione"
            hidden
        >
            @foreach ($module->lessons as $lesson)
                @include('tenant.admin.modules.partials.lesson-drawer-panel')
            @endforeach
        </aside>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-content-status-url]');
            if (!root) return;

            const endpoint = root.getAttribute('data-content-status-url');
            if (endpoint) {
                const pillClass = (status) => {
                    if (status === 'ready') return 'bg-success/15 text-success';
                    if (status === 'error') return 'bg-error/15 text-error';
                    return 'bg-warning/15 text-warning';
                };

                const paint = (el, status) => {
                    el.textContent = status;
                    el.className = `inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize ${pillClass(status)}`;
                };

                const refresh = async () => {
                    try {
                        const res = await fetch(endpoint, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-Skip-Loader': '1',
                            },
                        });
                        if (!res.ok) return;
                        const payload = await res.json();
                        const items = payload?.items || {};

                        document.querySelectorAll('[data-content-status][data-lesson-id]').forEach((el) => {
                            const lessonId = el.getAttribute('data-lesson-id');
                            const item = items[lessonId];
                            if (!item) return;
                            paint(el, item.status || 'processing');
                        });
                    } catch (_) {
                        // silent
                    }
                };

                refresh();
                setInterval(refresh, 8000);
            }

            const drawer = document.getElementById('lesson-drawer');
            const backdrop = document.getElementById('lesson-drawer-backdrop');
            if (!drawer || !backdrop) return;

            const panels = () => drawer.querySelectorAll('[data-lesson-panel]');
            let activeId = null;

            const openDrawer = (id) => {
                activeId = String(id);
                panels().forEach((panel) => {
                    const match = panel.getAttribute('data-lesson-panel') === activeId;
                    panel.hidden = !match;
                    panel.classList.toggle('hidden', !match);
                    panel.classList.toggle('flex', match);
                });
                document.querySelectorAll('[data-lesson-row]').forEach((row) => {
                    row.classList.toggle('is-active', row.getAttribute('data-lesson-row') === activeId);
                });
                drawer.hidden = false;
                backdrop.hidden = false;
                requestAnimationFrame(() => {
                    drawer.classList.add('is-open');
                    backdrop.classList.add('is-open');
                });
                document.body.classList.add('overflow-hidden');
            };

            const closeDrawer = () => {
                activeId = null;
                drawer.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                document.body.classList.remove('overflow-hidden');
                document.querySelectorAll('[data-lesson-row].is-active').forEach((row) => row.classList.remove('is-active'));
                window.setTimeout(() => {
                    if (drawer.classList.contains('is-open')) return;
                    drawer.hidden = true;
                    backdrop.hidden = true;
                    panels().forEach((panel) => {
                        panel.hidden = true;
                        panel.classList.add('hidden');
                        panel.classList.remove('flex');
                    });
                }, 220);
            };

            root.addEventListener('click', (e) => {
                const openBtn = e.target.closest('[data-lesson-open]');
                if (openBtn) {
                    e.preventDefault();
                    openDrawer(openBtn.getAttribute('data-lesson-open'));
                    return;
                }
                if (e.target.closest('[data-lesson-close]')) {
                    e.preventDefault();
                    closeDrawer();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                    closeDrawer();
                }
            });

            const typeSelect = document.getElementById('new_lesson_type');
            const durationInputs = () => document.querySelectorAll('[data-new-lesson-duration]');
            const syncNewLessonDurationVisibility = () => {
                const type = typeSelect?.value || '';
                const auto = type === 'video' || type === 'scorm';
                durationInputs().forEach((el) => {
                    el.hidden = auto;
                    el.disabled = auto;
                    if (auto) {
                        el.value = '';
                    }
                });
            };
            typeSelect?.addEventListener('change', syncNewLessonDurationVisibility);
            syncNewLessonDurationVisibility();
        })();
    </script>

    @push('scripts')
        @vite(['resources/js/video-player.js'])
    @endpush
</x-layouts.tenant>
