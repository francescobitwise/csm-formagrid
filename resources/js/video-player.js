import videojs from 'video.js';
import 'video.js/dist/video-js.css';

const DEFAULT_IDLE_MS = 5 * 60 * 1000;
const DEFAULT_GRACE_MS = 60 * 1000;

function parsePositiveMs(raw, fallback) {
    const n = Number.parseInt(raw || '', 10);
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

function boot() {
    const nodes = document.querySelectorAll('[data-videojs]');
    if (!nodes.length) return;

    nodes.forEach((el) => {
        if (el.dataset.videojsReady === '1') return;
        el.dataset.videojsReady = '1';

        const csrfToken = el.dataset.csrfToken || '';
        const videoLessonId = el.dataset.videoLessonId || '';
        const enrollmentId = el.dataset.enrollmentId || '';
        const logoutUrl = el.dataset.logoutUrl || '';
        const idleMs = parsePositiveMs(el.dataset.idleMs, DEFAULT_IDLE_MS);
        const graceMs = parsePositiveMs(el.dataset.graceMs, DEFAULT_GRACE_MS);
        const idleModalId = el.dataset.idleModal || 'video-idle-modal';
        const catalogDurParsed = Number.parseInt(el.dataset.catalogDuration || '', 10);
        const catalogDuration =
            Number.isFinite(catalogDurParsed) && catalogDurParsed > 0 ? catalogDurParsed : null;

        const useFill = Boolean(el.closest('.learner-video-shell'));

        const player = videojs(el, {
            controls: true,
            fill: useFill,
            fluid: ! useFill,
            responsive: true,
            playbackRates: [0.75, 1, 1.25, 1.5],
            html5: {
                vhs: {
                    overrideNative: !videojs.browser.IS_SAFARI,
                },
            },
        });

        // --- Idle logout (“Sei ancora qui?”) ---
        let idleTimer = null;
        let graceTimer = null;
        let graceTickTimer = null;
        let promptOpen = false;
        let loggingOut = false;

        const idleDialog = document.getElementById(idleModalId);
        const idleCountdownEl = idleDialog?.querySelector('[data-idle-countdown]') || null;
        const idleContinueBtn = idleDialog?.querySelector('[data-idle-continue]') || null;

        function clearIdle() {
            if (idleTimer !== null) {
                window.clearTimeout(idleTimer);
                idleTimer = null;
            }
        }

        function clearGrace() {
            if (graceTimer !== null) {
                window.clearTimeout(graceTimer);
                graceTimer = null;
            }
            if (graceTickTimer !== null) {
                window.clearInterval(graceTickTimer);
                graceTickTimer = null;
            }
        }

        function clearAllIdleTimers() {
            clearIdle();
            clearGrace();
        }

        function paintGraceCountdown(remainingMs) {
            if (!idleCountdownEl) return;
            const secs = Math.max(0, Math.ceil(remainingMs / 1000));
            idleCountdownEl.textContent = String(secs);
        }

        function closeIdlePrompt() {
            promptOpen = false;
            clearGrace();
            if (idleDialog?.open) {
                idleDialog.close();
            }
        }

        function performLogout() {
            if (loggingOut) return;
            loggingOut = true;
            clearAllIdleTimers();

            if (!logoutUrl || !csrfToken) {
                window.location.href = '/login';
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = logoutUrl;
            form.setAttribute('data-no-loader', '1');

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrfToken;
            form.appendChild(token);

            const reason = document.createElement('input');
            reason.type = 'hidden';
            reason.name = 'reason';
            reason.value = 'idle_video';
            form.appendChild(reason);

            document.body.appendChild(form);
            form.submit();
        }

        function openIdlePrompt() {
            if (promptOpen || loggingOut || !idleDialog) {
                if (!idleDialog && logoutUrl) {
                    performLogout();
                }
                return;
            }

            promptOpen = true;
            clearIdle();

            try {
                if (!player.paused()) {
                    player.pause();
                }
            } catch {
                /* ignore */
            }

            const graceEndsAt = Date.now() + graceMs;
            paintGraceCountdown(graceMs);

            if (typeof idleDialog.showModal === 'function') {
                idleDialog.showModal();
            } else {
                idleDialog.setAttribute('open', 'open');
            }

            graceTickTimer = window.setInterval(() => {
                paintGraceCountdown(graceEndsAt - Date.now());
            }, 250);

            graceTimer = window.setTimeout(() => {
                performLogout();
            }, graceMs);
        }

        function armIdle() {
            if (loggingOut || promptOpen) return;
            clearIdle();
            idleTimer = window.setTimeout(() => {
                openIdlePrompt();
            }, idleMs);
        }

        function onContinue() {
            if (loggingOut) return;
            closeIdlePrompt();
            armIdle();
        }

        if (idleDialog && logoutUrl && csrfToken) {
            idleDialog.addEventListener('cancel', (e) => {
                e.preventDefault();
            });

            idleContinueBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                onContinue();
            });

            player.on('play', () => {
                closeIdlePrompt();
                clearIdle();
            });

            player.on('pause', () => {
                armIdle();
            });

            player.on('ended', () => {
                armIdle();
            });

            // Pagina aperta senza play: parte subito il timer idle.
            player.ready(() => {
                if (player.paused()) {
                    armIdle();
                }
            });

            window.addEventListener('beforeunload', clearAllIdleTimers);

            player.on('dispose', () => {
                clearAllIdleTimers();
                closeIdlePrompt();
            });
        }

        const canReportProgress = csrfToken && videoLessonId && enrollmentId;
        if (!canReportProgress) {
            return;
        }

        let lastSaved = 0;
        let completionReported = false;
        let durationChangeSynced = false;

        function durationSeconds() {
            const fromPlayer = (() => {
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
                return null;
            })();

            // Se il catalogo è più lungo del media reale, per il completamento usa il media.
            if (fromPlayer !== null) {
                if (catalogDuration !== null && catalogDuration > fromPlayer) {
                    return fromPlayer;
                }
                return fromPlayer;
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
            const t = player.currentTime() || 0;
            const d = durationSeconds();
            if (d !== null && d > 0 && t >= d * 0.95) {
                return true;
            }
            try {
                const seekable = player.seekable();
                if (seekable && seekable.length > 0) {
                    const end = seekable.end(seekable.length - 1);
                    if (Number.isFinite(end) && end > 0 && t >= end * 0.95) {
                        return true;
                    }
                }
            } catch {
                /* ignore */
            }
            return false;
        }

        /** Il server valida incrementi e completamento (anti-salto a fine video). */
        function sendProgress(payload) {
            return fetch('/api/video/progress', {
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
            })
                .then(async (res) => {
                    if (! res.ok) {
                        return null;
                    }
                    try {
                        return await res.json();
                    } catch {
                        return null;
                    }
                })
                .then((data) => {
                    // Sblocca la lezione successiva ricaricando la sidebar/nav.
                    if (data?.newly_completed) {
                        window.setTimeout(() => window.location.reload(), 600);
                    }
                    return data;
                })
                .catch(() => null);
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
