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
                    <span id="video-status-badge" class="badge badge-ghost">
                        Video: <span class="ml-1 font-mono">—</span>
                    </span>
                    <span id="video-time-badge" class="badge badge-ghost gap-1">
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
                @if ($lesson->videoLesson && ($manifestUrl = $lesson->learnerHlsManifestUrl($course)))
                    <video
                        id="learner-video-player"
                        class="video-js vjs-big-play-centered w-full overflow-hidden rounded-xl bg-black/60"
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
                                statusBadge.className = 'badge ' + (completed ? 'badge-success' : 'badge-info');
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
                    <x-ui.alert type="warning">
                        Video non ancora disponibile (nessun manifest HLS configurato).
                    </x-ui.alert>
                @endif
            </div>
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
