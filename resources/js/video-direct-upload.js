/**
 * Upload video diretto su S3 (presigned PUT) + finalize su Laravel.
 * Richiede MEDIA_DISK=s3 e CORS sul bucket che consenta PUT dal dominio tenant.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function pickContentType(file) {
    if (file.type === 'video/mp4' || /\.mp4$/i.test(file.name)) {
        return 'video/mp4';
    }
    if (
        file.type === 'application/vnd.apple.mpegurl' ||
        file.type === 'application/x-mpegURL' ||
        /\.m3u8$/i.test(file.name)
    ) {
        return 'application/vnd.apple.mpegurl';
    }
    return 'video/mp4';
}

function setStatus(root, text, isError = false) {
    const el = root.querySelector('[data-direct-status]');
    if (!el) return;
    el.textContent = text;
    el.classList.toggle('text-rose-300', isError);
    el.classList.toggle('text-slate-400', !isError);
}

async function presign(root, contentType) {
    const res = await fetch(root.dataset.presignUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            module_id: root.dataset.moduleId,
            lesson_id: root.dataset.lessonId,
            content_type: contentType,
        }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.errors?.content_type?.[0] || 'Presign non riuscita.');
    }
    return data;
}

async function putToStorage(uploadUrl, headers, file, contentType) {
    const h = new Headers();
    h.set('Content-Type', contentType);
    if (headers && typeof headers === 'object') {
        Object.entries(headers).forEach(([name, value]) => {
            const lower = name.toLowerCase();
            if (lower === 'host') return;
            if (value != null && value !== '') {
                h.set(name, String(value));
            }
        });
    }

    const res = await fetch(uploadUrl, {
        method: 'PUT',
        headers: h,
        body: file,
    });
    if (!res.ok) {
        throw new Error(`Upload su storage fallito (HTTP ${res.status}).`);
    }
}

async function finalize(root, uploadToken) {
    const res = await fetch(root.dataset.finalizeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ upload_token: uploadToken }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || 'Registrazione upload non riuscita.');
    }
    return data;
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

        const contentType = pickContentType(file);
        btn.disabled = true;
        setStatus(root, 'Preparazione upload…');

        try {
            const presigned = await presign(root, contentType);
            setStatus(root, 'Trasferimento verso lo storage…');
            await putToStorage(presigned.upload_url, presigned.headers, file, contentType);
            setStatus(root, 'Registrazione in corso…');
            await finalize(root, presigned.upload_token);
            setStatus(root, 'Completato. Aggiorno la pagina…');
            window.location.reload();
        } catch (e) {
            setStatus(root, e.message || 'Errore sconosciuto.', true);
            btn.disabled = false;
        }
    });
}

export function initVideoDirectUpload() {
    document.querySelectorAll('[data-video-direct-upload]').forEach(bindRoot);
}
