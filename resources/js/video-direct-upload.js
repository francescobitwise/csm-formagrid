/**
 * Upload video diretto su S3 (presigned PUT) + finalize su Laravel.
 * Richiede MEDIA_DISK=s3 e CORS sul bucket che consenta PUT dal dominio tenant.
 */
import { hideUploadProgress, updateUploadProgress } from './upload-progress';
import { uploadVideoDirect } from './video-direct-upload-api';

function setStatus(root, text, isError = false) {
    const el = root.querySelector('[data-direct-status]');
    if (!el) return;
    el.textContent = text;
    el.classList.toggle('text-error', isError);
    el.classList.toggle('text-base-content/60', !isError);
}

function bindRoot(root) {
    const input = root.querySelector('[data-direct-file]');
    const btn = root.querySelector('[data-direct-submit]');
    if (!input || !btn) return;

    btn.addEventListener('click', async () => {
        const file = input.files?.[0];
        if (!file) {
            setStatus(root, 'Seleziona un file MP4 o M3U8.', true);
            return;
        }

        btn.disabled = true;
        setStatus(root, 'Preparazione upload…');
        updateUploadProgress(root, 0, '0%');

        try {
            setStatus(root, 'Trasferimento verso lo storage…');
            await uploadVideoDirect(
                {
                    presignUrl: root.dataset.presignUrl,
                    finalizeUrl: root.dataset.finalizeUrl,
                    moduleId: root.dataset.moduleId,
                    lessonId: root.dataset.lessonId,
                },
                file,
                (percent) => {
                    updateUploadProgress(root, percent, `${Math.round(percent)}%`);
                },
            );
            updateUploadProgress(root, 100, '100% — completato');
            setStatus(root, 'Completato. Aggiorno la pagina…');
            window.location.reload();
        } catch (e) {
            hideUploadProgress(root);
            setStatus(root, e.message || 'Errore sconosciuto.', true);
            btn.disabled = false;
        }
    });
}

export function initVideoDirectUpload() {
    document.querySelectorAll('[data-video-direct-upload]').forEach(bindRoot);
}
