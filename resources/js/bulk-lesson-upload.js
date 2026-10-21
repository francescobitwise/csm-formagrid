/**
 * Caricamento massivo lezioni video/SCORM dalla pagina moduli/lezioni.
 * Crea una lezione per file (titolo dal filename lato server) e carica in coda sequenziale.
 */
import { csrfToken, xhrUpload } from './upload-progress';
import { uploadVideoDirect } from './video-direct-upload-api';

function titlePreviewFromFilename(name) {
    const base = String(name || '')
        .replace(/^.*[\\/]/, '')
        .replace(/\.[^.]+$/, '');
    let cleaned = base.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
    if (cleaned.length < 2) cleaned = 'Lezione';
    return cleaned.slice(0, 200);
}

function inferLessonType(file) {
    const name = file.name || '';
    if (/\.zip$/i.test(name) || file.type === 'application/zip' || file.type === 'application/x-zip-compressed') {
        return 'scorm';
    }
    if (/\.(mp4|m3u8)$/i.test(name) || /^video\//.test(file.type) || /mpegurl/i.test(file.type || '')) {
        return 'video';
    }
    return null;
}

function firstValidationError(payload) {
    if (!payload || typeof payload !== 'object') return null;
    if (typeof payload.message === 'string' && payload.message) return payload.message;
    const errors = payload.errors;
    if (!errors || typeof errors !== 'object') return null;
    const firstKey = Object.keys(errors)[0];
    if (!firstKey) return null;
    const msgs = errors[firstKey];
    return Array.isArray(msgs) ? msgs[0] : String(msgs);
}

function scormUploadUrl(template, lessonId) {
    return String(template || '').replace('__LESSON__', encodeURIComponent(lessonId));
}

async function createLessonFromFile(root, file, type) {
    const res = await fetch(root.dataset.fromFileUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'X-Skip-Loader': '1',
        },
        body: JSON.stringify({
            type,
            original_filename: file.name,
            is_required: true,
        }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(firstValidationError(data) || data.message || 'Creazione lezione non riuscita.');
    }
    if (!data?.lesson?.id) {
        throw new Error('Risposta creazione lezione non valida.');
    }
    return data.lesson;
}

async function uploadScorm(root, lessonId, file, onProgress) {
    const body = new FormData();
    body.append('scorm_file', file);
    body.append('version', '1.2');

    const result = await xhrUpload({
        url: scormUploadUrl(root.dataset.scormUploadUrlTemplate, lessonId),
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Skip-Loader': '1',
        },
        body,
        onProgress,
    });

    if (result.status === 422) {
        throw new Error(firstValidationError(result.responseJSON) || 'Dati non validi.');
    }
    if (result.status >= 400) {
        throw new Error(
            firstValidationError(result.responseJSON) ||
                result.responseJSON?.message ||
                `Upload SCORM fallito (HTTP ${result.status}).`,
        );
    }
}

function renderQueue(listEl, items) {
    if (!listEl) return;
    listEl.innerHTML = items
        .map((item) => {
            const statusClass =
                item.status === 'error'
                    ? 'text-error'
                    : item.status === 'done'
                      ? 'text-success'
                      : 'text-base-content/60';
            const detail =
                item.status === 'error'
                    ? item.error || 'Errore'
                    : item.status === 'done'
                      ? 'Completato'
                      : item.status === 'uploading'
                        ? item.progressLabel || 'Caricamento…'
                        : item.status === 'creating'
                          ? 'Creazione lezione…'
                          : 'In coda';
            return `<li class="flex flex-col gap-0.5 border-b border-base-300/40 py-2 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-base-content">${escapeHtml(item.titlePreview)}</p>
                    <p class="truncate font-mono text-[11px] text-base-content/45">${escapeHtml(item.file.name)}</p>
                </div>
                <p class="shrink-0 text-xs tabular-nums ${statusClass}">${escapeHtml(detail)}</p>
            </li>`;
        })
        .join('');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function setBusy(root, busy) {
    const input = root.querySelector('[data-bulk-file]');
    const btn = root.querySelector('[data-bulk-submit]');
    if (input) input.disabled = busy;
    if (btn) btn.disabled = busy;
    root.classList.toggle('opacity-80', busy);
    root.classList.toggle('pointer-events-none', busy);
}

async function processQueue(root, items) {
    const listEl = root.querySelector('[data-bulk-queue]');
    const summaryEl = root.querySelector('[data-bulk-summary]');
    const input = root.querySelector('[data-bulk-file]');
    const btn = root.querySelector('[data-bulk-submit]');
    let failed = 0;
    let ok = 0;

    setBusy(root, true);
    if (summaryEl) {
        summaryEl.textContent = `Caricamento 0/${items.length}…`;
        summaryEl.classList.remove('text-error', 'text-success');
        summaryEl.classList.add('text-base-content/60');
    }

    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        item.status = 'creating';
        item.progressLabel = '';
        item.error = '';
        renderQueue(listEl, items);
        if (summaryEl) {
            summaryEl.textContent = `Caricamento ${i + 1}/${items.length}…`;
        }

        try {
            const lesson = await createLessonFromFile(root, item.file, item.type);
            item.titlePreview = lesson.title || item.titlePreview;
            item.status = 'uploading';
            renderQueue(listEl, items);

            if (item.type === 'video') {
                await uploadVideoDirect(
                    {
                        presignUrl: root.dataset.presignUrl,
                        finalizeUrl: root.dataset.finalizeUrl,
                        moduleId: root.dataset.moduleId,
                        lessonId: lesson.id,
                    },
                    item.file,
                    (percent) => {
                        item.progressLabel = `${Math.round(percent)}%`;
                        renderQueue(listEl, items);
                    },
                );
            } else {
                await uploadScorm(root, lesson.id, item.file, (percent) => {
                    item.progressLabel = `${Math.round(percent)}%`;
                    renderQueue(listEl, items);
                });
            }

            item.status = 'done';
            item.progressLabel = 'Completato';
            ok += 1;
        } catch (err) {
            item.status = 'error';
            item.error = err?.message || 'Errore sconosciuto.';
            failed += 1;
        }

        renderQueue(listEl, items);
    }

    setBusy(root, false);
    if (input) {
        input.value = '';
        input.disabled = false;
    }
    if (btn) {
        btn.disabled = true;
    }
    root.classList.remove('opacity-80', 'pointer-events-none');

    if (summaryEl) {
        if (failed === 0) {
            summaryEl.textContent = `${ok} file caricati. Aggiorno la pagina…`;
            summaryEl.classList.remove('text-error', 'text-base-content/60');
            summaryEl.classList.add('text-success');
            window.setTimeout(() => window.location.reload(), 600);
        } else if (ok === 0) {
            summaryEl.textContent = `Nessun file caricato (${failed} errori).`;
            summaryEl.classList.remove('text-success', 'text-base-content/60');
            summaryEl.classList.add('text-error');
        } else {
            summaryEl.textContent = `${ok} ok, ${failed} errori. Aggiorno la pagina…`;
            summaryEl.classList.remove('text-success', 'text-base-content/60');
            summaryEl.classList.add('text-error');
            window.setTimeout(() => window.location.reload(), 1200);
        }
    } else if (ok > 0) {
        window.setTimeout(() => window.location.reload(), 600);
    }
}

function bindBulkRoot(root) {
    if (root.dataset.bulkBound === '1') return;
    root.dataset.bulkBound = '1';

    const input = root.querySelector('[data-bulk-file]');
    const btn = root.querySelector('[data-bulk-submit]');
    const dropzone = root.querySelector('[data-bulk-dropzone]') || root;
    const listEl = root.querySelector('[data-bulk-queue]');
    const summaryEl = root.querySelector('[data-bulk-summary]');
    if (!input || !btn) return;

    let pendingItems = [];

    const syncPending = (files) => {
        const accepted = [];
        const rejected = [];
        Array.from(files || []).forEach((file) => {
            const type = inferLessonType(file);
            if (!type) {
                rejected.push(file.name);
                return;
            }
            accepted.push({
                file,
                type,
                titlePreview: titlePreviewFromFilename(file.name),
                status: 'queued',
                progressLabel: '',
                error: '',
            });
        });
        pendingItems = accepted;
        renderQueue(listEl, pendingItems);
        if (summaryEl) {
            if (rejected.length && accepted.length === 0) {
                summaryEl.textContent = 'Solo MP4, M3U8 o ZIP SCORM.';
                summaryEl.classList.add('text-error');
                summaryEl.classList.remove('text-success', 'text-base-content/60');
            } else if (rejected.length) {
                summaryEl.textContent = `${accepted.length} file pronti · ${rejected.length} ignorati (tipo non supportato).`;
                summaryEl.classList.remove('text-error', 'text-success');
                summaryEl.classList.add('text-base-content/60');
            } else if (accepted.length) {
                summaryEl.textContent = `${accepted.length} file pronti.`;
                summaryEl.classList.remove('text-error', 'text-success');
                summaryEl.classList.add('text-base-content/60');
            } else {
                summaryEl.textContent = '';
            }
        }
        btn.disabled = accepted.length === 0;
    };

    input.addEventListener('change', () => {
        syncPending(input.files);
    });

    ['dragenter', 'dragover'].forEach((evt) => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach((evt) => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });
    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer?.files;
        if (!files?.length) return;
        syncPending(files);
        try {
            const dt = new DataTransfer();
            pendingItems.forEach((item) => dt.items.add(item.file));
            input.files = dt.files;
        } catch (_) {
            // DataTransfer non disponibile: la coda in memoria resta valida
        }
    });

    btn.addEventListener('click', async () => {
        if (!pendingItems.length) {
            if (summaryEl) {
                summaryEl.textContent = 'Seleziona uno o più file.';
                summaryEl.classList.add('text-error');
            }
            return;
        }
        const items = pendingItems.slice();
        pendingItems = [];
        btn.disabled = true;
        await processQueue(root, items);
    });

    btn.disabled = true;
}

export function initBulkLessonUpload() {
    document.querySelectorAll('[data-bulk-lesson-upload]').forEach(bindBulkRoot);
}
