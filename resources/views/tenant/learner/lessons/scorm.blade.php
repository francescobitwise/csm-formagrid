<x-layouts.tenant :title="$lesson->title">
    <x-lesson-player-layout
        :course="$course"
        :lesson="$lesson"
        :completedLessonIds="$completedLessonIds"
        :completedCount="$completedCount"
        :totalCount="$totalCount"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="lesson-player-breadcrumb">
                    <a href="{{ route('tenant.courses.show', $course) }}" class="lesson-player-breadcrumb-link">{{ $course->title }}</a>
                    <span class="lesson-player-breadcrumb-sep">/</span>
                    <span class="lesson-player-breadcrumb-current">{{ $lesson->title }}</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">{{ $lesson->title }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span id="scorm-status-badge" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-200">
                        SCORM: <span class="ml-1 font-mono">—</span>
                    </span>
                    <span id="scorm-time-badge" class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-200">
                        <i class="ph ph-clock" aria-hidden="true"></i>
                        <span class="font-mono">0:00</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (!empty($prevLessonId))
                    <a href="{{ route('tenant.lessons.show', [$course, $prevLessonId]) }}"
                       class="admin-btn-secondary inline-flex items-center gap-2 px-3 py-2 text-xs">
                        <i class="ph ph-arrow-left" aria-hidden="true"></i>
                        Lezione precedente
                    </a>
                @endif
                @if (!empty($nextLessonId))
                    <a href="{{ route('tenant.lessons.show', [$course, $nextLessonId]) }}"
                       class="admin-btn-primary inline-flex items-center gap-2 px-3 py-2 text-xs">
                        Lezione successiva
                        <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-6 glass-card rounded-2xl border border-white/5 p-6">
            @if ($lesson->scormPackage)

                @php($launchPath = (string) (data_get($lesson->scormPackage->manifest, 'launch_path') ?: 'index.html'))
                <div class="overflow-hidden rounded-xl border border-white/5 bg-slate-950/50">
                    <iframe
                        id="scorm-frame"
                        title="SCORM Player"
                        src="{{ route('tenant.scorm.asset', ['package' => $lesson->scormPackage->id, 'path' => $launchPath]) }}"
                        data-package-id="{{ $lesson->scormPackage->id }}"
                        data-enrollment-id="{{ $enrollment->id }}"
                        data-csrf-token="{{ csrf_token() }}"
                        class="h-[70vh] w-full bg-slate-950"
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

                    // Fallback: alcuni pacchetti non invocano mai l'API SCORM.
                    // Ping leggero per registrare tempo SOLO quando la pagina è attiva (no tab in background / no inattività).
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
                        statusBadge.className =
                            'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
                        statusBadge.classList.add(
                            ok ? 'border-lime-500/25' : fail ? 'border-rose-500/25' : 'border-amber-500/25',
                            ok ? 'bg-lime-500/10' : fail ? 'bg-rose-500/10' : 'bg-amber-500/10',
                            ok ? 'text-lime-200' : fail ? 'text-rose-200' : 'text-amber-100'
                        );
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
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-300">
                    Pacchetto SCORM non disponibile.
                </div>
            @endif
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>

