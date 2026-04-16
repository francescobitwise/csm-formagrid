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
                    <span id="video-status-badge" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-200">
                        Video: <span class="ml-1 font-mono">—</span>
                    </span>
                    <span id="video-time-badge" class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-200">
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
            @if ($lesson->videoLesson && ($manifestUrl = $lesson->learnerHlsManifestUrl($course)))
                <video
                    id="learner-video-player"
                    class="video-js vjs-big-play-centered w-full rounded-xl overflow-hidden bg-black/60"
                    playsinline
                    data-videojs="1"
                    data-csrf-token="{{ csrf_token() }}"
                    data-video-lesson-id="{{ $lesson->videoLesson->id }}"
                    data-enrollment-id="{{ $enrollment->id }}"
                    @if (($catalogDur = $lesson->videoLesson->duration_seconds ?? $lesson->duration_seconds) && $catalogDur > 0)
                        data-catalog-duration="{{ (int) $catalogDur }}"
                    @endif
                    @if ($poster = $lesson->videoLesson->posterPublicUrl()) poster="{{ $poster }}" @endif
                >
                    <source src="{{ $manifestUrl }}" type="application/x-mpegURL">
                    <track kind="captions" srclang="it" label="Italiano" src="{{ asset('brand/empty-captions.vtt') }}" default>
                </video>

                @push('scripts')
                    @vite(['resources/js/video-player.js'])
                    <script>
                        const videoEl = document.getElementById('learner-video-player');
                        const statusBadge = document.getElementById('video-status-badge');
                        const timeBadge = document.getElementById('video-time-badge');

                        function formatMmss(total) {
                            const s = Math.max(0, Number(total || 0) | 0);
                            const m = Math.floor(s / 60);
                            const ss = String(s % 60).padStart(2, '0');
                            return `${m}:${ss}`;
                        }

                        function paintVideo(completed) {
                            if (!statusBadge) return;
                            statusBadge.className =
                                'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
                            statusBadge.classList.add(
                                completed ? 'border-lime-500/25' : 'border-sky-500/25',
                                completed ? 'bg-lime-500/10' : 'bg-sky-500/10',
                                completed ? 'text-lime-200' : 'text-sky-200'
                            );
                            statusBadge.textContent = completed ? 'Video: completato' : 'Video: in corso';
                        }

                        async function refreshVideoStatus() {
                            const videoLessonId = videoEl?.dataset?.videoLessonId || '';
                            const enrollmentId = videoEl?.dataset?.enrollmentId || '';
                            if (!videoLessonId || !enrollmentId) return;
                            try {
                                const url = new URL('/api/video/status', window.location.origin);
                                url.searchParams.set('video_lesson_id', videoLessonId);
                                url.searchParams.set('enrollment_id', enrollmentId);
                                const res = await fetch(url.toString(), {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Skip-Loader': '1' },
                                    credentials: 'same-origin',
                                });
                                if (!res.ok) return;
                                const data = await res.json();
                                paintVideo(Boolean(data?.completed));
                                if (timeBadge) {
                                    const span = timeBadge.querySelector('span');
                                    if (span) span.textContent = formatMmss(data?.watched_seconds || 0);
                                }
                            } catch (_) {}
                        }

                        refreshVideoStatus();
                        setInterval(refreshVideoStatus, 8000);
                    </script>
                @endpush
            @else
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-300">
                    Video non ancora disponibile (nessun manifest HLS configurato).
                </div>
            @endif
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
