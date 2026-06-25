<x-layouts.tenant :title="$lesson->title">
    <x-lesson-player-layout
        :course="$course"
        :lesson="$lesson"
        :completedLessonIds="$completedLessonIds"
        :accessibleLessonIds="$accessibleLessonIds"
        :completedCount="$completedCount"
        :totalCount="$totalCount"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li><a href="{{ route('tenant.courses.show', $course) }}">{{ $course->title }}</a></li>
                        <li class="max-w-[16rem] truncate">{{ $lesson->title }}</li>
                    </ul>
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $lesson->title }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span id="scorm-status-badge" class="badge badge-ghost">
                        SCORM: <span class="ml-1 font-mono">—</span>
                    </span>
                    <span id="scorm-time-badge" class="badge badge-ghost gap-1">
                        <i class="ph ph-clock" aria-hidden="true"></i>
                        <span class="font-mono">0:00</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (!empty($prevLessonId))
                    <x-ui.button href="{{ route('tenant.lessons.show', [$course, $prevLessonId]) }}" variant="secondary" size="sm" icon="ph-arrow-left">
                        Lezione precedente
                    </x-ui.button>
                @endif
                @if (!empty($nextLessonId))
                    <x-ui.button href="{{ route('tenant.lessons.show', [$course, $nextLessonId]) }}" size="sm">
                        Lezione successiva
                        <i class="ph ph-arrow-right"></i>
                    </x-ui.button>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body p-4 sm:p-6">
                @if ($lesson->scormPackage)

                    @php($launchPath = (string) (data_get($lesson->scormPackage->manifest, 'launch_path') ?: 'index.html'))
                    <div class="overflow-hidden rounded-xl border border-base-300 bg-base-200">
                        <iframe
                            id="scorm-frame"
                            title="SCORM Player"
                            src="{{ route('tenant.scorm.asset', ['package' => $lesson->scormPackage->id, 'path' => $launchPath]) }}"
                            data-package-id="{{ $lesson->scormPackage->id }}"
                            data-enrollment-id="{{ $enrollment->id }}"
                            data-csrf-token="{{ csrf_token() }}"
                            class="h-[70vh] w-full bg-base-300"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <script>
                        const scormFrame = document.getElementById('scorm-frame');
                        globalThis.initScormRuntime?.({
                            packageId: scormFrame?.dataset?.packageId || '',
                            enrollmentId: scormFrame?.dataset?.enrollmentId || '',
                            csrfToken: scormFrame?.dataset?.csrfToken || '',
                        });

                        (function startScormWatchPing() {
                            const packageId = scormFrame?.dataset?.packageId || '';
                            const enrollmentId = scormFrame?.dataset?.enrollmentId || '';
                            const csrfToken = scormFrame?.dataset?.csrfToken || '';
                            if (!packageId || !enrollmentId || !csrfToken) return;

                            let lastActiveAt = Date.now();

                            const bump = () => { lastActiveAt = Date.now(); };
                            window.addEventListener('mousemove', bump, { passive: true });
                            window.addEventListener('keydown', bump);
                            window.addEventListener('scroll', bump, { passive: true });
                            window.addEventListener('pointerdown', bump, { passive: true });
                            window.addEventListener('focus', bump);

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

                        const statusBadge = document.getElementById('scorm-status-badge');
                        const timeBadge = document.getElementById('scorm-time-badge');

                        function formatMmss(total) {
                            const s = Math.max(0, Number(total || 0) | 0);
                            const m = Math.floor(s / 60);
                            const ss = String(s % 60).padStart(2, '0');
                            return `${m}:${ss}`;
                        }

                        function paintStatus(status) {
                            const st = String(status || 'incomplete');
                            const ok = st === 'completed' || st === 'passed';
                            const fail = st === 'failed' || st === 'error';
                            if (!statusBadge) return;
                            statusBadge.className = 'badge ' + (ok ? 'badge-success' : fail ? 'badge-error' : 'badge-warning');
                            statusBadge.textContent = `SCORM: ${st}`;
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
                                const pct = Number.isFinite(Number(data?.progress_pct)) ? Number(data?.progress_pct) : null;
                                paintStatus(pct != null ? `${data?.status} · ${pct}%` : data?.status);
                                if (timeBadge) {
                                    const span = timeBadge.querySelector('span');
                                    if (span) span.textContent = formatMmss(data?.watched_seconds || 0);
                                }
                            } catch (_) {}
                        }

                        refreshScormStatus();
                        setInterval(refreshScormStatus, 8000);
                    </script>
                @else
                    <x-ui.alert type="warning">
                        Pacchetto SCORM non disponibile.
                    </x-ui.alert>
                @endif
            </div>
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
