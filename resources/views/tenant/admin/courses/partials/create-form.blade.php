<form method="post"
      enctype="multipart/form-data"
      action="{{ route('tenant.admin.courses.store') }}"
      class="space-y-5"
      id="create-course-form">
    @csrf
    <input type="hidden" name="form_intent" value="create_course">

    <p class="text-sm text-base-content/65">Titolo, date e stato editoriale. Nel catalogo compaiono solo i corsi Published.</p>

    <div class="form-control w-full">
        <label class="label" for="create_course_thumbnail">
            <span class="label-text">Immagine catalogo (copertina)</span>
        </label>
        <p class="mb-2 text-xs text-base-content/60">Formato immagine, max 5&nbsp;MB.</p>
        <input id="create_course_thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="file-input file-input-bordered w-full">
        @error('thumbnail') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_course_title">
            <span class="label-text">Titolo</span>
        </label>
        <input id="create_course_title" name="title" class="input input-bordered w-full" value="{{ old('title') }}" required>
        @error('title') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="form-control w-full">
            <label class="label" for="create_course_slug">
                <span class="label-text">Slug (opzionale)</span>
            </label>
            <input id="create_course_slug" name="slug" class="input input-bordered w-full font-mono" value="{{ old('slug') }}" placeholder="es. leadership-avanzata">
            @error('slug') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label" for="create_course_status">
                <span class="label-text">Stato</span>
            </label>
            <select id="create_course_status" name="status" class="select select-bordered w-full" required>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(old('status', 'draft') === $s->value)>
                        {{ $s->label() }}
                    </option>
                @endforeach
            </select>
            @error('status') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_course_description">
            <span class="label-text">Descrizione</span>
        </label>
        <textarea id="create_course_description" name="description" class="textarea textarea-bordered h-24 w-full resize-none" placeholder="Obiettivi e contenuti...">{{ old('description') }}</textarea>
        @error('description') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="form-control w-full">
            <label class="label" for="create_course_starts_at">
                <span class="label-text">Data/ora inizio (opzionale)</span>
            </label>
            <input id="create_course_starts_at" type="datetime-local" name="starts_at" class="input input-bordered w-full"
                   value="{{ old('starts_at') }}">
            @error('starts_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Durata totale (opzionale)</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label py-0" for="create_course_duration_hours">
                        <span class="label-text text-xs text-base-content/60">Ore</span>
                    </label>
                    <input id="create_course_duration_hours" type="number" name="duration_hours" min="0" max="99999" step="1" class="input input-bordered w-full"
                           value="{{ old('duration_hours') }}" placeholder="0">
                </div>
                <div>
                    <label class="label py-0" for="create_course_duration_minutes">
                        <span class="label-text text-xs text-base-content/60">Minuti</span>
                    </label>
                    <input id="create_course_duration_minutes" type="number" name="duration_minutes" min="0" max="59" step="1" class="input input-bordered w-full"
                           value="{{ old('duration_minutes') }}" placeholder="0">
                </div>
            </div>
            @error('duration_hours') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
            @error('duration_minutes') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="form-control w-full">
            <label class="label" for="create_course_assigned_company_ids">
                <span class="label-text">Disponibile per aziende</span>
            </label>
            <select id="create_course_assigned_company_ids" name="assigned_company_ids[]" class="select select-bordered h-32 w-full" multiple>
                @php($selectedCompanies = old('assigned_company_ids', []))
                @foreach(($createCompanies ?? []) as $company)
                    <option value="{{ $company->id }}" @selected(in_array($company->id, $selectedCompanies))>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('assigned_company_ids') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label" for="create_course_assigned_user_ids">
                <span class="label-text">Disponibile per corsisti specifici</span>
            </label>
            <select id="create_course_assigned_user_ids" name="assigned_user_ids[]" class="select select-bordered h-32 w-full" multiple>
                @php($selectedLearners = old('assigned_user_ids', []))
                @foreach(($createLearners ?? []) as $learner)
                    <option value="{{ $learner->id }}" @selected(in_array($learner->id, $selectedLearners))>
                        {{ $learner->name }} — {{ $learner->email }}
                    </option>
                @endforeach
            </select>
            @error('assigned_user_ids') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="border border-base-300 bg-base-200/40 p-4">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="hidden" name="auto_enroll" value="0">
            <input type="checkbox"
                   name="auto_enroll"
                   value="1"
                   class="checkbox checkbox-primary mt-0.5"
                   @checked(old('auto_enroll', false))>
            <span>
                <span class="block text-sm font-medium">Iscrivi automaticamente al corso</span>
                <span class="mt-1 block text-xs text-base-content/60">
                    Alla salvataggio, iscrive i corsisti assegnati senza che debbano cliccare «Iscriviti».
                </span>
            </span>
        </label>
        @error('auto_enroll') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
    </div>
</form>
