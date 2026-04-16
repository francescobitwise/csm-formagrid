@php
    $doc = $lesson->documentLesson;
    $pdfUrl = ($doc && $doc->file_path) ? \App\Support\MediaStorage::url($doc->file_path) : null;
@endphp
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
                    <span class="inline-flex items-center rounded-full border border-violet-500/25 bg-violet-500/10 px-2.5 py-1 text-xs font-semibold text-violet-100">
                        Documento
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-200">
                        <i class="ph ph-info" aria-hidden="true"></i>
                        Nessun tracking tempo
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

        <div class="mt-6 glass-card rounded-2xl border border-white/5 p-4 sm:p-6">
            @if ($pdfUrl)
                <p class="mb-3 text-xs text-slate-500">
                    @if ($doc->original_filename)
                        <span class="text-slate-400">{{ $doc->original_filename }}</span>
                        <span class="mx-1">·</span>
                    @endif
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" class="text-brand-blue/90 underline decoration-brand-blue/30 underline-offset-2 hover:text-brand-amber">
                        Apri in una nuova scheda
                    </a>
                </p>
                <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-950/40">
                    <iframe
                        title="{{ $lesson->title }}"
                        src="{{ $pdfUrl }}#view=FitH"
                        class="h-[min(85vh,900px)] w-full border-0 bg-white"
                    ></iframe>
                </div>
            @else
                <div class="rounded-xl border border-amber-500/25 bg-amber-500/5 px-4 py-3 text-sm text-amber-100/90">
                    Contenuto non ancora disponibile: un amministratore deve caricare il PDF dalla gestione lezioni del modulo.
                </div>
            @endif
        </div>
    </x-lesson-player-layout>
</x-layouts.tenant>
