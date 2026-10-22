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
                                    const mediaDuration = longestMediaSeconds();
                                    if (mediaDuration) payload.media_duration_seconds = mediaDuration;
                                    return payload;
                                })()),
                            });
                        } catch (_) {}
                    }

                    document.addEventListener('visibilitychange', syncPlayback);
                    syncPlayback();
                    setInterval(syncPlayback, 1000);
                    ping();
                    setInterval(ping, 10000);
                })();

                const hadNextButton = @json(! empty($nextLessonId));
                let reloadedAfterCompletion = false;

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
                        const serverPct = data?.progress_pct;
                        const pct = typeof serverPct === 'number' ? serverPct : observedMediaPercent();
                        paintLessonProgress(pct);
                        const st = String(data?.status || '');
                        if ((st === 'completed' || st === 'passed') && !hadNextButton && !reloadedAfterCompletion) {
                            reloadedAfterCompletion = true;
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
