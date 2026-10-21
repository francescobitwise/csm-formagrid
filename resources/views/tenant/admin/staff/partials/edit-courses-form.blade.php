<form method="post" action="{{ route('tenant.admin.staff.courses.update', $user) }}" class="space-y-4" id="edit-inspector-courses-form-{{ $user->id }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="form_intent" value="edit_inspector_courses_{{ $user->id }}">

    <p class="text-sm text-base-content/65">Seleziona uno o più corsi di cui {{ $user->name }} può vedere e scaricare i report.</p>

    <div class="form-control w-full">
        <label class="label" for="edit_inspector_course_ids_{{ $user->id }}">
            <span class="label-text">Corsi assegnati</span>
        </label>
        <select id="edit_inspector_course_ids_{{ $user->id }}" name="course_ids[]" class="select select-bordered h-40 w-full" multiple required>
            @php($selected = old('course_ids', $user->inspectedCourses->pluck('id')->all()))
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected(in_array($course->id, $selected, true))>{{ $course->title }}</option>
            @endforeach
        </select>
        @error('course_ids') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        @error('course_ids.*') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>
</form>
