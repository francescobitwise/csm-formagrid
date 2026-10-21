@php
    $prefix = (string) ($prefix ?? 'session');
    $startedValue = (string) ($started ?? '');
    $endedValue = (string) ($ended ?? '');
    $videoValue = $videoMinutes ?? 0;
    $scormValue = $scormMinutes ?? 0;
    $selectedLesson = (string) ($lessonId ?? '');
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    <div class="form-control w-full">
        <label class="label py-1" for="started_at_{{ $prefix }}">
            <span class="label-text text-xs font-semibold">Inizio</span>
        </label>
        <input id="started_at_{{ $prefix }}" type="datetime-local" name="started_at" required
               value="{{ $startedValue }}"
               class="input input-bordered w-full input-sm">
    </div>
    <div class="form-control w-full">
        <label class="label py-1" for="ended_at_{{ $prefix }}">
            <span class="label-text text-xs font-semibold">Fine</span>
        </label>
        <input id="ended_at_{{ $prefix }}" type="datetime-local" name="ended_at" required
               value="{{ $endedValue }}"
               class="input input-bordered w-full input-sm">
    </div>
    <div class="form-control w-full">
        <label class="label py-1" for="video_minutes_{{ $prefix }}">
            <span class="label-text text-xs font-semibold">Minuti video</span>
        </label>
        <input id="video_minutes_{{ $prefix }}" type="number" min="0" step="1" name="video_minutes" required
               value="{{ $videoValue }}"
               class="input input-bordered w-full input-sm">
    </div>
    <div class="form-control w-full">
        <label class="label py-1" for="scorm_minutes_{{ $prefix }}">
            <span class="label-text text-xs font-semibold">Minuti SCORM</span>
        </label>
        <input id="scorm_minutes_{{ $prefix }}" type="number" min="0" step="1" name="scorm_minutes" required
               value="{{ $scormValue }}"
               class="input input-bordered w-full input-sm">
    </div>
</div>
<div class="form-control w-full">
    <label class="label py-1" for="lesson_id_{{ $prefix }}">
        <span class="label-text text-xs font-semibold">Lezione</span>
    </label>
    <select id="lesson_id_{{ $prefix }}" name="lesson_id" class="select select-bordered w-full select-sm">
        <option value="">Nessuna lezione</option>
        @foreach ($lessons as $lesson)
            <option value="{{ $lesson->id }}" @selected($selectedLesson === (string) $lesson->id)>
                {{ $lesson->title }}
            </option>
        @endforeach
    </select>
</div>
