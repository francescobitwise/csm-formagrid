<x-layouts.tenant :title="'Modifica corso — Admin'">
    <x-ui.page>
        <x-ui.page-header
            title="Modifica corso"
            subtitle="Titolo, date, stato editoriale. Nel catalogo compaiono solo i corsi Published (e dopo la data di inizio, se impostata)."
        >
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.courses.index') }}" class="link link-hover">Corsi</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">Modifica</span>
            </x-slot:breadcrumb>
            <x-slot:actions>
                <a href="{{ route('tenant.admin.courses.index') }}" class="btn btn-ghost btn-sm">
                    &larr; Torna ai corsi
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="border-b border-base-300 px-4 py-6 lg:px-6">
            <form method="post"
                  enctype="multipart/form-data"
                  action="{{ route('tenant.admin.courses.update', $course) }}"
                  class="mx-auto max-w-3xl space-y-6">
                @csrf
                @method('put')

                <div class="form-control w-full">
                    <label class="label" for="thumbnail">
                        <span class="label-text">Immagine catalogo (copertina)</span>
                    </label>
                    <p class="mb-2 text-xs text-base-content/60">Visibile nel catalogo corsi e in testa alla scheda corso. Formato immagine, max 5&nbsp;MB.</p>
                    @if ($course->thumbnailPublicUrl())
                        <div class="mb-3 flex flex-wrap items-center gap-4">
                            <img src="{{ $course->thumbnailPublicUrl() }}" alt="" class="h-24 w-40 border border-base-300 object-cover" loading="lazy">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="hidden" name="remove_thumbnail" value="0">
                                <input type="checkbox" name="remove_thumbnail" value="1" class="checkbox checkbox-primary">
                                Rimuovi copertina
                            </label>
                        </div>
                    @endif
                    <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="file-input file-input-bordered w-full">
                    @error('thumbnail') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-control w-full">
                    <label class="label" for="title">
                        <span class="label-text">Titolo</span>
                    </label>
                    <input id="title" name="title" class="input input-bordered w-full" value="{{ old('title', $course->title) }}" required>
                    @error('title') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="form-control w-full">
                        <label class="label" for="slug">
                            <span class="label-text">Slug (opzionale)</span>
                        </label>
                        <input id="slug" name="slug" class="input input-bordered w-full font-mono" value="{{ old('slug', $course->slug) }}" placeholder="es. leadership-avanzata">
                        @error('slug') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label" for="status">
                            <span class="label-text">Stato</span>
                        </label>
                        <select id="status" name="status" class="select select-bordered w-full" required>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected(old('status', (string) ($course->status?->value ?? $course->status ?? 'draft'))===$s->value)>
                                    {{ $s->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/60">Solo <strong>{{ \App\Enums\CourseStatus::Published->label() }}</strong> è visibile nel catalogo learner; <strong>{{ \App\Enums\CourseStatus::Draft->label() }}</strong> e <strong>{{ \App\Enums\CourseStatus::Archived->label() }}</strong> restano in admin.</p>
                        @error('status') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-control w-full">
                    <label class="label" for="description">
                        <span class="label-text">Descrizione</span>
                    </label>
                    <textarea id="description" name="description" class="textarea textarea-bordered w-full h-28 resize-none" placeholder="Obiettivi e contenuti...">{{ old('description', $course->description) }}</textarea>
                    @error('description') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="form-control w-full">
                        <label class="label" for="starts_at">
                            <span class="label-text">Data/ora inizio (opzionale)</span>
                        </label>
                        <input id="starts_at" type="datetime-local" name="starts_at" class="input input-bordered w-full"
                               value="{{ old('starts_at', $course->starts_at?->format('Y-m-d\TH:i')) }}">
                        <p class="mt-1 text-xs text-base-content/60">Prima di questa data il corso non compare nel catalogo learner.</p>
                        @error('starts_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-control w-full">
                        @php
                            $hasOldDuration = old('duration_hours') !== null || old('duration_minutes') !== null;
                            $totalMinutesFromCourse = (! $hasOldDuration && $course->total_hours !== null)
                                ? (int) round((float) $course->total_hours * 60)
                                : null;
                            $durationHours = old('duration_hours', $totalMinutesFromCourse !== null ? intdiv($totalMinutesFromCourse, 60) : null);
                            $durationMinutes = old('duration_minutes', $totalMinutesFromCourse !== null ? $totalMinutesFromCourse % 60 : null);
                        @endphp
                        <label class="label">
                            <span class="label-text">Durata totale (opzionale)</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label py-0" for="duration_hours">
                                    <span class="label-text text-xs text-base-content/60">Ore</span>
                                </label>
                                <input id="duration_hours" type="number" name="duration_hours" min="0" max="99999" step="1" class="input input-bordered w-full"
                                       value="{{ $durationHours }}" placeholder="0">
                            </div>
                            <div>
                                <label class="label py-0" for="duration_minutes">
                                    <span class="label-text text-xs text-base-content/60">Minuti</span>
                                </label>
                                <input id="duration_minutes" type="number" name="duration_minutes" min="0" max="59" step="1" class="input input-bordered w-full"
                                       value="{{ $durationMinutes }}" placeholder="0">
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-base-content/60">Ore e minuti (es. 2 ore e 30 minuti).</p>
                        @error('duration_hours') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        @error('duration_minutes') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                @php
                    $weekdayLabels = \App\Services\CourseScheduleService::WEEKDAY_LABELS;
                    $selectedWeekdays = old('schedule_weekdays', $course->schedule_weekdays ?? []);
                    if (! is_array($selectedWeekdays)) {
                        $selectedWeekdays = [];
                    }
                    $selectedWeekdays = array_map('intval', $selectedWeekdays);
                    $fmtTime = function ($value) {
                        if ($value === null || $value === '') {
                            return '';
                        }
                        $str = (string) $value;
                        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
                            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
                        }

                        return '';
                    };
                    $scheduleEnabled = (bool) old('schedule_enabled', $course->schedule_enabled ?? false);
                    $nightEnabled = (bool) old('night_schedule_enabled', $course->night_schedule_enabled ?? false);
                @endphp

                <div class="border border-base-300 bg-base-200/40 p-4 space-y-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="hidden" name="schedule_enabled" value="0">
                        <input type="checkbox"
                               name="schedule_enabled"
                               value="1"
                               id="schedule_enabled"
                               class="checkbox checkbox-primary mt-0.5"
                               @checked($scheduleEnabled)
                               onchange="document.getElementById('schedule-fields').classList.toggle('hidden', !this.checked)">
                        <span>
                            <span class="block text-sm font-medium">Limita accessi per giorno/orario</span>
                            <span class="mt-1 block text-xs text-base-content/60">
                                Fuori dagli orari il corso resta visibile ma risulta «Corso chiuso». Fuso orario: Europe/Rome.
                            </span>
                        </span>
                    </label>

                    <div id="schedule-fields" class="space-y-4 {{ $scheduleEnabled ? '' : 'hidden' }}">
                        <div>
                            <span class="label-text text-sm font-medium">Giorni della settimana</span>
                            <div class="mt-2 flex flex-wrap gap-3">
                                @foreach ($weekdayLabels as $dayNum => $dayLabel)
                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               name="schedule_weekdays[]"
                                               value="{{ $dayNum }}"
                                               class="checkbox checkbox-sm checkbox-primary"
                                               @checked(in_array($dayNum, $selectedWeekdays, true))>
                                        {{ $dayLabel }}
                                    </label>
                                @endforeach
                            </div>
                            @error('schedule_weekdays') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-control w-full">
                                <label class="label" for="schedule_opens_at">
                                    <span class="label-text">Apertura</span>
                                </label>
                                <input id="schedule_opens_at" type="time" name="schedule_opens_at" class="input input-bordered w-full"
                                       value="{{ old('schedule_opens_at', $fmtTime($course->schedule_opens_at)) }}">
                                @error('schedule_opens_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-control w-full">
                                <label class="label" for="schedule_closes_at">
                                    <span class="label-text">Chiusura</span>
                                </label>
                                <input id="schedule_closes_at" type="time" name="schedule_closes_at" class="input input-bordered w-full"
                                       value="{{ old('schedule_closes_at', $fmtTime($course->schedule_closes_at)) }}">
                                @error('schedule_closes_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="hidden" name="night_schedule_enabled" value="0">
                            <input type="checkbox"
                                   name="night_schedule_enabled"
                                   value="1"
                                   id="night_schedule_enabled"
                                   class="checkbox checkbox-primary mt-0.5"
                                   @checked($nightEnabled)
                                   onchange="document.getElementById('night-schedule-fields').classList.toggle('hidden', !this.checked)">
                            <span>
                                <span class="block text-sm font-medium">Fascia notturna (solo override)</span>
                                <span class="mt-1 block text-xs text-base-content/60">
                                    Accessibile solo agli allievi con «Override orari notturni». Può attraversare la mezzanotte (es. 22:00–06:00).
                                </span>
                            </span>
                        </label>

                        <div id="night-schedule-fields" class="grid gap-4 sm:grid-cols-2 {{ $nightEnabled ? '' : 'hidden' }}">
                            <div class="form-control w-full">
                                <label class="label" for="night_opens_at">
                                    <span class="label-text">Apertura notturna</span>
                                </label>
                                <input id="night_opens_at" type="time" name="night_opens_at" class="input input-bordered w-full"
                                       value="{{ old('night_opens_at', $fmtTime($course->night_opens_at)) }}">
                                @error('night_opens_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-control w-full">
                                <label class="label" for="night_closes_at">
                                    <span class="label-text">Chiusura notturna</span>
                                </label>
                                <input id="night_closes_at" type="time" name="night_closes_at" class="input input-bordered w-full"
                                       value="{{ old('night_closes_at', $fmtTime($course->night_closes_at)) }}">
                                @error('night_closes_at') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="form-control w-full">
                        <label class="label" for="assigned_company_ids">
                            <span class="label-text">Disponibile per aziende</span>
                        </label>
                        <p class="mb-2 text-xs text-base-content/60">Seleziona una o più aziende. Se vuoto, il corso non compare ai corsisti (a meno di assegnazione diretta).</p>
                        <select id="assigned_company_ids" name="assigned_company_ids[]" class="select select-bordered w-full h-40" multiple>
                            @php($selectedCompanies = old('assigned_company_ids', $assignedCompanyIds ?? []))
                            @foreach(($companies ?? []) as $company)
                                <option value="{{ $company->id }}" @selected(in_array($company->id, $selectedCompanies, true))>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_company_ids') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        @error('assigned_company_ids.*') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label" for="assigned_user_ids">
                            <span class="label-text">Disponibile per corsisti specifici</span>
                        </label>
                        <p class="mb-2 text-xs text-base-content/60">Assegnazione diretta a singoli corsisti (oltre alle aziende). Utile per eccezioni.</p>
                        <select id="assigned_user_ids" name="assigned_user_ids[]" class="select select-bordered w-full h-40" multiple>
                            @php($selectedLearners = old('assigned_user_ids', $assignedLearnerIds ?? []))
                            @foreach(($learners ?? []) as $learner)
                                <option value="{{ $learner->id }}" @selected(in_array($learner->id, $selectedLearners, true))>
                                    {{ $learner->name }} — {{ $learner->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_ids') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                        @error('assigned_user_ids.*') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="border border-base-300 bg-base-200/40 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="hidden" name="auto_enroll" value="0">
                        <input type="checkbox"
                               name="auto_enroll"
                               value="1"
                               class="checkbox checkbox-primary mt-0.5"
                               @checked(old('auto_enroll', (bool) ($course->auto_enroll ?? false)))>
                        <span>
                            <span class="block text-sm font-medium">Iscrivi automaticamente al corso</span>
                            <span class="mt-1 block text-xs text-base-content/60">
                                Alla salvataggio, iscrive i corsisti assegnati (diretti o tramite azienda) senza che debbano cliccare «Iscriviti».
                                Aggiungendo nuove assegnazioni, salva di nuovo per iscrivere i nuovi utenti.
                            </span>
                        </span>
                    </label>
                    @error('auto_enroll') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="flex items-center gap-3">
                        <button class="btn btn-primary">
                            Salva
                        </button>
                        <a href="{{ route('tenant.admin.courses.builder', $course) }}" class="btn btn-outline">
                            Moduli del corso
                        </a>
                    </div>
                </div>
            </form>

            <form method="post" action="{{ route('tenant.admin.courses.destroy', $course) }}"
                  class="mx-auto mt-8 flex max-w-3xl justify-end border-t border-base-300 pt-6"
                  onsubmit="return confirm('Eliminare il corso?')">
                @csrf
                @method('delete')
                <button class="btn btn-error btn-outline">
                    Elimina
                </button>
            </form>
        </div>
    </x-ui.page>
</x-layouts.tenant>
