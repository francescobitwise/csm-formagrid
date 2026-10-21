/**
 * Intercetta form multipart (SCORM / PDF) e mostra barra di progresso.
 */
import { csrfToken, hideUploadProgress, updateUploadProgress, xhrUpload } from './upload-progress';

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

function bindForm(form) {
    if (form.dataset.uploadBound === '1') return;
    form.dataset.uploadBound = '1';

    const root = form.closest('[data-upload-root]') || form;
    const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    const statusEl = root.querySelector('[data-upload-status]');

    const setStatus = (text, isError = false) => {
        if (!statusEl) return;
        statusEl.textContent = text || '';
        statusEl.classList.toggle('text-error', isError);
        statusEl.classList.toggle('text-base-content/60', !isError);
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && (!fileInput.files || fileInput.files.length === 0)) {
            setStatus('Seleziona un file.', true);
            return;
        }

        submitBtns.forEach((b) => {
            b.disabled = true;
        });
        setStatus('Caricamento in corso…');
        updateUploadProgress(root, 0, '0%');

        try {
            const body = new FormData(form);
            const result = await xhrUpload({
                url: form.action,
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Skip-Loader': '1',
                },
                body,
                onProgress: (percent) => {
                    updateUploadProgress(root, percent, `${Math.round(percent)}%`);
                },
            });

            if (result.status === 422) {
                throw new Error(firstValidationError(result.responseJSON) || 'Dati non validi.');
            }

            if (result.status >= 400) {
                throw new Error(
                    firstValidationError(result.responseJSON) ||
                        result.responseJSON?.message ||
                        `Upload fallito (HTTP ${result.status}).`,
                );
            }

            updateUploadProgress(root, 100, '100% — completato');
            setStatus('Completato. Aggiorno la pagina…');
            window.location.reload();
        } catch (err) {
            hideUploadProgress(root);
            setStatus(err.message || 'Errore sconosciuto.', true);
            submitBtns.forEach((b) => {
                b.disabled = false;
            });
        }
    });
}

export function initFormUploadProgress() {
    document.querySelectorAll('form[data-upload-form]').forEach(bindForm);
}
