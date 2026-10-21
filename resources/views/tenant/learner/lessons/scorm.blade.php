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

                    function shouldPing() {
                        if (document.visibilityState !== 'visible') return false;
                        if (!document.hasFocus()) return false;
                        return true;
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
                                body: JSON.stringify({
                                    package_id: packageId,
                                    enrollment_id: enrollmentId,
                                    data: { __event: 'ping' },
                                }),
                            });
                        } catch (_) {}
                    }

                    ping();
                    setInterval(ping, 10000);
                })();

                const hadNextButton = @json(! empty($nextLessonId));
                let reloadedAfterCompletion = false;

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
