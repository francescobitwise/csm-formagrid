@php
    $doc = $lesson->documentLesson;
    $pdfUrl = ($doc && $doc->file_path) ? \App\Support\MediaStorage::url($doc->file_path) : null;
@endphp
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

            @if ($pdfUrl)
                <div class="border-b border-base-300 px-4 py-2 text-xs text-base-content/60">
                    @if ($doc->original_filename)
                        <span>{{ $doc->original_filename }}</span>
                        <span class="mx-1">·</span>
                    @endif
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" class="link link-primary">
                        Apri in una nuova scheda
                    </a>
                </div>
                <iframe
                    title="{{ $lesson->title }}"
                    src="{{ $pdfUrl }}#view=FitH"
                    class="learner-doc-frame bg-white"
                ></iframe>
            @else
                <div class="p-4">
                    <x-ui.alert type="warning">
                        Contenuto non ancora disponibile: un amministratore deve caricare il PDF dalla gestione lezioni del modulo.
                    </x-ui.alert>
                </div>
            @endif
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
