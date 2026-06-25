@php
    $doc = $lesson->documentLesson;
    $pdfUrl = ($doc && $doc->file_path) ? \App\Support\MediaStorage::url($doc->file_path) : null;
@endphp
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
                    <span class="badge badge-secondary">Documento</span>
                    <span class="badge badge-ghost gap-1">
                        <i class="ph ph-info" aria-hidden="true"></i>
                        Nessun tracking tempo
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
                @if ($pdfUrl)
                    <p class="mb-3 text-xs text-base-content/60">
                        @if ($doc->original_filename)
                            <span>{{ $doc->original_filename }}</span>
                            <span class="mx-1">·</span>
                        @endif
                        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" class="link link-primary">
                            Apri in una nuova scheda
                        </a>
                    </p>
                    <div class="overflow-hidden rounded-xl border border-base-300 bg-base-200">
                        <iframe
                            title="{{ $lesson->title }}"
                            src="{{ $pdfUrl }}#view=FitH"
                            class="h-[min(85vh,900px)] w-full border-0 bg-white"
                        ></iframe>
                    </div>
                @else
                    <x-ui.alert type="warning">
                        Contenuto non ancora disponibile: un amministratore deve caricare il PDF dalla gestione lezioni del modulo.
                    </x-ui.alert>
                @endif
            </div>
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
