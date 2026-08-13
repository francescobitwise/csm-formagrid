/**
 * Helper condivisi per upload video diretto (presign → PUT S3 → finalize).
 */
import { csrfToken, xhrUpload } from './upload-progress';

export function pickVideoContentType(file) {
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

/**
 * @param {{
 *   presignUrl: string,
 *   finalizeUrl: string,
 *   moduleId: string,
 *   lessonId: string,
 * }} ctx
 * @param {File} file
 * @param {(percent: number) => void} [onProgress]
 */
export async function uploadVideoDirect(ctx, file, onProgress) {
    const contentType = pickVideoContentType(file);

    const presignRes = await fetch(ctx.presignUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'X-Skip-Loader': '1',
        },
        body: JSON.stringify({
            module_id: ctx.moduleId,
            lesson_id: ctx.lessonId,
            content_type: contentType,
            expected_size: file.size || undefined,
        }),
    });
    const presigned = await presignRes.json().catch(() => ({}));
    if (!presignRes.ok) {
        throw new Error(
            presigned.message ||
                presigned.errors?.content_type?.[0] ||
                'Presign non riuscita.',
        );
    }

    const hdrs = { 'Content-Type': contentType };
    if (presigned.headers && typeof presigned.headers === 'object') {
        Object.entries(presigned.headers).forEach(([name, value]) => {
            const lower = name.toLowerCase();
            if (lower === 'host') return;
            if (value != null && value !== '') {
                hdrs[name] = String(value);
            }
        });
    }

    const putResult = await xhrUpload({
        url: presigned.upload_url,
        method: 'PUT',
        headers: hdrs,
        body: file,
        onProgress,
    });

    if (putResult.status < 200 || putResult.status >= 300) {
        throw new Error(`Upload su storage fallito (HTTP ${putResult.status}).`);
    }

    const finalizeRes = await fetch(ctx.finalizeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'X-Skip-Loader': '1',
        },
        body: JSON.stringify({ upload_token: presigned.upload_token }),
    });
    const finalized = await finalizeRes.json().catch(() => ({}));
    if (!finalizeRes.ok) {
        throw new Error(finalized.message || 'Registrazione upload non riuscita.');
    }

    return finalized;
}
