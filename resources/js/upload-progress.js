/**
 * Helper UI + XHR upload con progresso (video/SCORM/PDF).
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * @param {ParentNode} root
 * @param {number|null} percent 0–100, null = indeterminate / hide value
 * @param {string} [label]
 */
export function updateUploadProgress(root, percent, label) {
    const wrap = root.querySelector('[data-upload-progress]');
    const bar = root.querySelector('[data-upload-progress-bar]');
    const text = root.querySelector('[data-upload-progress-label]');
    if (!wrap || !bar) return;

    wrap.hidden = false;
    wrap.classList.remove('hidden');

    if (percent == null || Number.isNaN(percent)) {
        bar.removeAttribute('value');
    } else {
        const p = Math.max(0, Math.min(100, Math.round(percent)));
        bar.value = p;
        bar.setAttribute('value', String(p));
        if (text && !label) {
            text.textContent = `${p}%`;
        }
    }

    if (text && label) {
        text.textContent = label;
    }
}

/**
 * @param {ParentNode} root
 */
export function hideUploadProgress(root) {
    const wrap = root.querySelector('[data-upload-progress]');
    if (!wrap) return;
    wrap.hidden = true;
    wrap.classList.add('hidden');
}

/**
 * PUT/POST con progresso upload (XHR).
 * @param {{
 *   url: string,
 *   method?: string,
 *   headers?: Record<string, string>,
 *   body: Document|Blob|FormData|string,
 *   onProgress?: (percent: number) => void,
 * }} opts
 * @returns {Promise<{ status: number, responseText: string, responseJSON: any }>}
 */
export function xhrUpload(opts) {
    const method = opts.method || 'POST';
    const headers = opts.headers || {};

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, opts.url, true);

        Object.entries(headers).forEach(([name, value]) => {
            if (value != null && value !== '') {
                xhr.setRequestHeader(name, String(value));
            }
        });

        xhr.upload.onprogress = (e) => {
            if (!opts.onProgress) return;
            if (e.lengthComputable && e.total > 0) {
                opts.onProgress((e.loaded / e.total) * 100);
            } else {
                opts.onProgress(0);
            }
        };

        xhr.onload = () => {
            let responseJSON = null;
            try {
                responseJSON = JSON.parse(xhr.responseText);
            } catch (_) {
                // non-JSON (redirect HTML, ecc.)
            }
            resolve({
                status: xhr.status,
                responseText: xhr.responseText,
                responseJSON,
            });
        };

        xhr.onerror = () => reject(new Error('Connessione interrotta durante l’upload.'));
        xhr.onabort = () => reject(new Error('Upload annullato.'));

        xhr.send(opts.body);
    });
}
