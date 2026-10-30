<x-layouts.tenant :title="$lesson->title">
    <x-lesson-player-layout
        :course="$course"
        :lesson="$lesson"
        :enrollment="$enrollment"
        :completedLessonIds="$completedLessonIds"
        :accessibleLessonIds="$accessibleLessonIds"
        :completedCount="$completedCount"
        :totalCount="$totalCount"
        :lessonProgressPct="$lessonProgressPct"
    >
        @if ($lesson->scormPackage)
            @php
                $launchPath = (string) (data_get($lesson->scormPackage->manifest, 'launch_path') ?: 'index.html');
                $slideW = (int) (data_get($lesson->scormPackage->manifest, 'slide_width') ?: 16);
                $slideH = (int) (data_get($lesson->scormPackage->manifest, 'slide_height') ?: 9);
                if ($slideW < 1 || $slideH < 1) {
                    $slideW = 16;
                    $slideH = 9;
                }
            @endphp
            <div class="learner-stage-frame learner-stage-frame--scorm">
                @include('tenant.learner.lessons.partials.content-head', [
                    'course' => $course,
                    'lesson' => $lesson,
                    'showLessonNav' => false,
                ])
                @if ($enrollment->status === \App\Enums\EnrollmentStatus::Completed && empty($nextLessonId))
                    <div class="px-4 pt-3">
                        <x-ui.alert type="success">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-medium">Hai completato il corso.</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('tenant.courses.certificate', $course) }}" class="btn btn-sm btn-outline gap-2">
                                        <i class="ph ph-certificate" aria-hidden="true"></i>
                                        Scarica certificato
                                    </a>
                                    <a href="{{ route('tenant.courses.show', $course) }}" class="btn btn-sm btn-ghost">Torna al corso</a>
                                </div>
                            </div>
                        </x-ui.alert>
                    </div>
                @endif
                <div
                    class="learner-scorm-shell"
                    style="--scorm-ar-w: {{ $slideW }}; --scorm-ar-h: {{ $slideH }};"
                >
                    <iframe
                        id="scorm-frame"
                        title="{{ $lesson->title }}"
                        src="{{ route('tenant.scorm.asset', ['package' => $lesson->scormPackage->id, 'path' => $launchPath]) }}"
                        data-package-id="{{ $lesson->scormPackage->id }}"
                        data-enrollment-id="{{ $enrollment->id }}"
                        data-csrf-token="{{ csrf_token() }}"
                        class="learner-scorm-frame"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
            <script type="application/json" id="scorm-initial-cmi">@json($scormInitialCmi ?? null)</script>
            <script>
                const scormFrame = document.getElementById('scorm-frame');
                const initialCmi = (() => {
                    try {
                        return JSON.parse(document.getElementById('scorm-initial-cmi')?.textContent || 'null');
                    } catch (_) {
                        return null;
                    }
                })();
                globalThis.initScormRuntime?.({
                    packageId: scormFrame?.dataset?.packageId || '',
                    enrollmentId: scormFrame?.dataset?.enrollmentId || '',
                    csrfToken: scormFrame?.dataset?.csrfToken || '',
                    initialCmi,
                });

                (function startScormWatchPing() {
                    const packageId = scormFrame?.dataset?.packageId || '';
                    const enrollmentId = scormFrame?.dataset?.enrollmentId || '';
                    const csrfToken = scormFrame?.dataset?.csrfToken || '';
                    if (!packageId || !enrollmentId || !csrfToken) return;

                    function lessonIsActive() {
                        return document.visibilityState === 'visible';
                    }

                    function syncPlayback() {
                        const active = lessonIsActive();
                        function visit(win, depth) {
                            if (!win || depth > 6) return;
                            try {
                                win.document.querySelectorAll('video, audio').forEach((el) => {
                                    if (!el.__lmsPlayGuard) {
                                        el.__lmsPlayGuard = true;
                                        el.addEventListener('play', () => {
                                            if (!lessonIsActive()) {
                                                el.dataset.lmsHold = '1';
                                                el.pause();
                                            }
                                        });
                                    }
                                    if (!active) {
                                        if (!el.paused) {
                                            el.dataset.lmsHold = '1';
                                            el.pause();
                                        }
                                    } else if (el.dataset.lmsHold === '1') {
                                        delete el.dataset.lmsHold;
                                        el.play().catch(() => {});
                                    }
                                });
                                win.document.querySelectorAll('iframe').forEach((frame) => visit(frame.contentWindow, depth + 1));
                            } catch (_) {}
                        }
                        visit(window, 0);
                    }

                    function shouldPing() {
                        return lessonIsActive();
                    }

                    function clockToSeconds(text) {
                        const parts = String(text).split(':').map((part) => Number(part));
                        if (parts.length < 2 || parts.some((part) => !Number.isFinite(part))) return null;
                        if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
                        return parts[0] * 60 + parts[1];
                    }

                    function readPlayerClock(win, depth) {
                        if (!win || depth > 6) return null;
                        let found = null;
                        const consider = (text) => {
                            const match = String(text).replace(/\s+/g, ' ').trim().match(/^(\d{1,2}:\d{2}(?::\d{2})?)\s*\/\s*(\d{1,2}:\d{2}(?::\d{2})?)$/);
                            if (!match) return;
                            const position = clockToSeconds(match[1]);
                            const duration = clockToSeconds(match[2]);
                            if (position === null || duration === null || duration <= 0) return;
                            if (!found || duration > found.duration) found = { position, duration };
                        };
                        try {
                            win.document.querySelectorAll('span, div, p, b, strong, label, time').forEach((el) => {
                                if (el.childElementCount > 6) return;
                                const text = (el.textContent || '').trim();
                                if (text.length === 0 || text.length > 24) return;
                                consider(text);
                            });
                            win.document.querySelectorAll('iframe').forEach((frame) => {
                                const inner = readPlayerClock(frame.contentWindow, depth + 1);
                                if (inner && (!found || inner.duration > found.duration)) found = inner;
                            });
                        } catch (_) {}
                        return found;
                    }

                    let lastClockPosition = null;
                    let clockAdvancedAt = null;
                    let sawMediaAt = null;
                    let earnedSeconds = null;
                    let lastAccountAt = null;

                    function mediaSnapshot() {
                        function bestIn(win, depth) {
                            if (!win || depth > 6) return null;
                            let best = null;
                            try {
                                win.document.querySelectorAll('video, audio').forEach((el) => {
                                    const duration = Number(el.duration);
                                    const position = Number(el.currentTime);
                                    if (!Number.isFinite(duration) || duration < 1 || !Number.isFinite(position)) return;
                                    if (!best || duration > best.duration) {
                                        best = {
                                            position: Math.floor(position),
                                            duration: Math.round(duration),
                                            playing: !el.paused && !el.ended,
                                        };
                                    }
                                });
                                win.document.querySelectorAll('iframe').forEach((frame) => {
                                    const inner = bestIn(frame.contentWindow, depth + 1);
                                    if (inner && (!best || inner.duration > best.duration)) best = inner;
                                });
                            } catch (_) {}
                            return best;
                        }

                        const media = bestIn(scormFrame?.contentWindow, 0);
                        if (media) return media;

                        const clock = readPlayerClock(scormFrame?.contentWindow, 0);
                        if (!clock) return null;
                        if (lastClockPosition !== null && clock.position > lastClockPosition) {
                            clockAdvancedAt = Date.now();
                        }
                        lastClockPosition = clock.position;
                        const playing = clockAdvancedAt !== null && (Date.now() - clockAdvancedAt) < 2500;
                        return {
                            position: Math.floor(clock.position),
                            duration: Math.round(clock.duration),
                            playing,
                        };
                    }

                    function playbackSample() {
                        const snap = mediaSnapshot();
                        if (!snap || snap.duration <= 0) return null;
                        const now = Date.now();
                        if (sawMediaAt === null) sawMediaAt = now;
                        if (snap.position <= 0 && (now - sawMediaAt) < 1500) return null;
                        return snap;
                    }

                    function accountPlayback(snap) {
                        if (!snap) return;
                        const now = Date.now();
                        if (earnedSeconds === null) {
                            const bar = document.querySelector('[data-current-lesson="1"] progress');
                            const shownPct = bar ? Number(bar.value) : 0;
                            const shownSeconds = Math.round((shownPct / 100) * snap.duration);
                            earnedSeconds = Math.min(shownSeconds, snap.position);
                        } else if (snap.playing && lastAccountAt !== null) {
                            const wall = Math.min(15, Math.max(0, (now - lastAccountAt) / 1000));
                            const advanced = Math.max(0, snap.position - earnedSeconds);
                            earnedSeconds += Math.min(wall, advanced);
                        }
                        lastAccountAt = now;
                        if (scormFrame) scormFrame.dataset.playbackTracked = '1';
                        paintLessonProgress(Math.min(99, Math.floor((earnedSeconds / snap.duration) * 100)));
                    }

                    function longestMediaSeconds() {
                        function bestIn(win, depth) {
                            if (!win || depth > 5) return null;
                            let best = 0;
                            try {
                                win.document.querySelectorAll('video').forEach((video) => {
                                    const duration = Number(video.duration);
                                    if (Number.isFinite(duration) && duration > best) best = duration;
                                });
                                win.document.querySelectorAll('iframe').forEach((frame) => {
                                    const inner = bestIn(frame.contentWindow, depth + 1);
                                    if (inner && inner > best) best = inner;
                                });
                            } catch (_) {}
                            return best > 0 ? best : null;
                        }

                        const duration = bestIn(scormFrame?.contentWindow, 0);
                        if (!duration || duration < 60) return null;
                        return Math.round(duration);
                    }

                    async function ping() {
                        if (!shouldPing()) return;
                        const snap = playbackSample();
                        if (snap) accountPlayback(snap);
                        try {
                            await fetch('/api/scorm/track', {
                                method: 'PUT',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-Skip-Loader': '1',
                                },
                                body: JSON.stringify((() => {
                                    const payload = {
                                        package_id: packageId,
                                        enrollment_id: enrollmentId,
                                        data: { __event: 'ping' },
                                    };
                                    if (snap) {
                                        payload.media_playing = snap.playing;
                                        payload.media_position_seconds = snap.position;
                                        if (snap.duration >= 60) payload.media_duration_seconds = snap.duration;
                                    }
                                    return payload;
                                })()),
                            });
                        } catch (_) {}
                    }

                    document.addEventListener('visibilitychange', syncPlayback);
                    syncPlayback();
                    setInterval(() => {
                        syncPlayback();
                        const snap = playbackSample();
                        if (snap) accountPlayback(snap);
                    }, 1000);
                    ping();
                    setInterval(ping, 10000);
                })();

                const hadNextButton = @json(! empty($nextLessonId));
                const lessonAlreadyCompleted = @json($completedLessonIds->contains($lesson->id));
                const completionReloadKey = 'scorm-completion-reload:{{ $lesson->id }}';

                function observedMediaPercent() {
                    function bestIn(win, depth) {
                        if (!win || depth > 5) return null;
                        let best = null;
                        try {
                            win.document.querySelectorAll('video').forEach((video) => {
                                const duration = Number(video.duration);
                                const current = Number(video.currentTime);
                                if (!Number.isFinite(duration) || duration < 30 || !Number.isFinite(current)) return;
                                if (!best || duration > best.duration) best = { duration, current };
                            });
                            win.document.querySelectorAll('iframe').forEach((frame) => {
                                const inner = bestIn(frame.contentWindow, depth + 1);
                                if (inner && (!best || inner.duration > best.duration)) best = inner;
                            });
                        } catch (_) {}
                        return best;
                    }

                    const media = bestIn(scormFrame?.contentWindow, 0);
                    if (!media || media.duration <= 0) return null;
                    return Math.min(99, Math.floor((media.current / media.duration) * 100));
                }

                function paintLessonProgress(pct) {
                    if (pct === null || pct === undefined || Number.isNaN(Number(pct))) return;
                    const value = Math.max(0, Math.min(100, Math.round(Number(pct))));
                    const current = document.querySelector('[data-current-lesson="1"]');
                    const bar = current?.querySelector('progress');
                    if (bar) {
                        bar.value = value;
                        bar.setAttribute('aria-label', 'Progresso lezione ' + value + ' percento');
                    }
                    const label = current?.querySelector('[data-lesson-pct]');
                    if (label) {
                        label.textContent = value + '%';
                        label.classList.toggle('hidden', value <= 0);
                    }
                    const bars = [...document.querySelectorAll('progress[data-required-lesson="1"]')];
                    if (!bars.length) return;
                    const avg = Math.min(100, Math.floor(bars.reduce((sum, node) => sum + Number(node.value || 0), 0) / bars.length));
                    const wrap = document.querySelector('[data-course-progress]');
                    const ring = document.querySelector('[data-course-progress-ring]');
                    const ringLabel = document.querySelector('[data-course-progress-label]');
                    if (ringLabel) ringLabel.textContent = avg + '%';
                    if (wrap) {
                        wrap.setAttribute('aria-valuenow', String(avg));
                        wrap.setAttribute('aria-label', 'Progresso corso ' + avg + ' percento');
                    }
                    if (ring) {
                        const circumference = Number(ring.getAttribute('data-circumference') || 0);
                        ring.setAttribute('stroke-dashoffset', String(circumference * (1 - avg / 100)));
                    }
                }

                async function refreshScormStatus() {
                    const packageId = scormFrame?.dataset?.packageId || '';
                    const enrollmentId = scormFrame?.dataset?.enrollmentId || '';
                    if (!packageId || !enrollmentId) return;
                    try {
                        const url = new URL('/api/scorm/status', window.location.origin);
                        url.searchParams.set('package_id', packageId);
                        url.searchParams.set('enrollment_id', enrollmentId);
                        const res = await fetch(url.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Skip-Loader': '1' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const st = String(data?.status || '');
                        if (st === 'completed' || st === 'passed') {
                            paintLessonProgress(100);
                        } else if (scormFrame?.dataset?.playbackTracked !== '1') {
                            const serverPct = data?.progress_pct;
                            const pct = typeof serverPct === 'number' ? serverPct : observedMediaPercent();
                            paintLessonProgress(pct);
                        }
                        // Un solo reload se la lezione si è appena conclusa e il pulsante
                        // «successiva» non c'era ancora. Se era già completata, un altro
                        // reload non cambia nulla e la pagina si ricarica all'infinito.
                        if ((st === 'completed' || st === 'passed') && !hadNextButton && !lessonAlreadyCompleted && !sessionStorage.getItem(completionReloadKey)) {
                            sessionStorage.setItem(completionReloadKey, '1');
                            setTimeout(() => window.location.reload(), 1200);
                        }
                    } catch (_) {}
                }

                refreshScormStatus();
                setInterval(refreshScormStatus, 8000);
            </script>
        @else
            <div class="learner-stage-frame p-4">
                <x-ui.alert type="warning">
                    Pacchetto SCORM non disponibile.
                </x-ui.alert>
            </div>
        @endif
    </x-lesson-player-layout>
</x-layouts.tenant>
