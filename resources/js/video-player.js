import videojs from 'video.js';
import 'video.js/dist/video-js.css';

function boot() {
    const nodes = document.querySelectorAll('[data-videojs]');
    if (!nodes.length) return;

    nodes.forEach((el) => {
        if (el.dataset.videojsReady === '1') return;
        el.dataset.videojsReady = '1';

        const csrfToken = el.dataset.csrfToken || '';
        const videoLessonId = el.dataset.videoLessonId || '';
        const enrollmentId = el.dataset.enrollmentId || '';
        const catalogDurParsed = Number.parseInt(el.dataset.catalogDuration || '', 10);
        const catalogDuration =
            Number.isFinite(catalogDurParsed) && catalogDurParsed > 0 ? catalogDurParsed : null;

        const player = videojs(el, {
            controls: true,
            fluid: true,
            responsive: true,
            playbackRates: [0.75, 1, 1.25, 1.5],
            html5: {
                vhs: {
                    overrideNative: !videojs.browser.IS_SAFARI,
                },
            },
        });

        const canReportProgress = csrfToken && videoLessonId && enrollmentId;
        if (!canReportProgress) {
            return;
        }

        let lastSaved = 0;
        let completionReported = false;
        let durationChangeSynced = false;

        function durationSeconds() {
            const raw = player.duration();
            if (raw != null && Number.isFinite(raw) && raw > 0) {
                return Math.floor(raw);
            }
            try {
                const seekable = player.seekable();
                if (seekable && seekable.length > 0) {
                    const end = seekable.end(seekable.length - 1);
                    if (Number.isFinite(end) && end > 0) {
                        return Math.floor(end);
                    }
                }
            } catch {
                /* ignore */
            }
            return catalogDuration;
        }

        /** Su `ended` alcuni engine riportano currentTime 0: usiamo la durata nota. */
        function currentProgressSeconds() {
            const d = durationSeconds();
            const t = Math.floor(player.currentTime() || 0);
            if (player.ended() && d !== null) {
                return d;
            }
            if (d !== null) {
                return Math.min(Math.max(0, t), d);
            }
            return t;
        }

        /** HLS spesso non emette `ended`; consideriamo completata la visione oltre ~95% o a fine riproduzione. */
        function shouldMarkComplete() {
            if (player.ended()) {
                return true;
            }
            const d = durationSeconds();
            if (d === null || d <= 0) {
                return false;
            }
            return (player.currentTime() || 0) >= d * 0.95;
        }

        /** Il server valida incrementi e completamento (anti-salto a fine video). */
        function sendProgress(payload) {
            fetch('/api/video/progress', {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Skip-Loader': '1',
                },
                body: JSON.stringify(payload),
            }).catch(() => {});
        }

        function buildProgressPayload(includeComplete) {
            const now = currentProgressSeconds();
            const dur = durationSeconds();
            const nearEnd = shouldMarkComplete();
            const markDone = includeComplete || nearEnd;
            if (markDone) {
                completionReported = true;
            }
            const payload = {
                video_lesson_id: videoLessonId,
                enrollment_id: enrollmentId,
                watched_seconds: dur === null ? now : Math.min(now, dur),
                last_position: now,
            };
            if (dur !== null) {
                payload.duration_seconds = dur;
            }
            if (markDone) {
                payload.completed = true;
            }
            return payload;
        }

        player.on('timeupdate', () => {
            const now = currentProgressSeconds();
            const nearEnd = shouldMarkComplete();
            const intervalOk = now - lastSaved >= 5;
            if (nearEnd && completionReported === false) {
                lastSaved = now;
                sendProgress(buildProgressPayload(true));
                return;
            }
            if (intervalOk) {
                lastSaved = now;
                sendProgress(buildProgressPayload(false));
            }
        });

        player.on('ended', () => {
            lastSaved = currentProgressSeconds();
            sendProgress(buildProgressPayload(true));
        });

        player.on('pause', () => {
            lastSaved = currentProgressSeconds();
            sendProgress(buildProgressPayload(false));
        });

        player.on('durationchange', () => {
            if (durationChangeSynced || durationSeconds() === null) {
                return;
            }
            durationChangeSynced = true;
            sendProgress(buildProgressPayload(false));
        });
    });

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
