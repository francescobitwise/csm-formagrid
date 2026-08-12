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
        <div class="learner-stage-frame">
            @include('tenant.learner.lessons.partials.content-head', [
                'course' => $course,
                'lesson' => $lesson,
                'prevLessonId' => $prevLessonId ?? null,
                'nextLessonId' => $nextLessonId ?? null,
            ])

            @if ($lesson->videoLesson && ($manifestUrl = $lesson->learnerHlsManifestUrl($course)))
                <div class="learner-video-shell">
                    <video
                        id="learner-video-player"
                        class="video-js vjs-big-play-centered"
                        playsinline
                        data-videojs="1"
                        data-csrf-token="{{ csrf_token() }}"
                        data-video-lesson-id="{{ $lesson->videoLesson->id }}"
                        data-enrollment-id="{{ $enrollment->id }}"
                        data-logout-url="{{ route('tenant.logout') }}"
                        data-idle-modal="video-idle-modal"
                        data-idle-ms="300000"
                        data-grace-ms="60000"
                        @if (($catalogDur = $lesson->videoLesson->duration_seconds ?? $lesson->duration_seconds) && $catalogDur > 0)
                            data-catalog-duration="{{ (int) $catalogDur }}"
                        @endif
                        @if ($poster = $lesson->videoLesson->posterPublicUrl()) poster="{{ $poster }}" @endif
                    >
                        <source src="{{ $manifestUrl }}" type="application/x-mpegURL">
                        <track kind="captions" srclang="it" label="Italiano" src="{{ asset('brand/empty-captions.vtt') }}" default>
                    </video>
                </div>

                <dialog
                    id="video-idle-modal"
                    class="modal"
                    aria-labelledby="video-idle-modal-title"
                >
                    <div class="modal-box max-w-md">
                        <h3 id="video-idle-modal-title" class="text-lg font-semibold text-base-content">
                            Sei ancora qui?
                        </h3>
                        <p class="mt-3 text-sm text-base-content/70">
                            Non stai riproducendo il video. Conferma entro
                            <span class="font-mono font-semibold text-base-content" data-idle-countdown>60</span>
                            secondi oppure verrai disconnesso.
                        </p>
                        <div class="modal-action">
                            <button type="button" class="btn btn-primary" data-idle-continue>
                                Continua
                            </button>
                        </div>
                    </div>
                </dialog>

                @push('scripts')
                    @vite(['resources/js/video-player.js'])
                @endpush
            @else
                <div class="p-4">
                    <x-ui.alert type="warning">
                        Video non ancora disponibile (nessun manifest HLS configurato).
                    </x-ui.alert>
                </div>
            @endif
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
