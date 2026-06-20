<x-layouts.tenant :title="($course->exists ? 'Modifica corso' : 'Nuovo corso').' — Admin'">
    <div class="mx-auto max-w-[960px] px-6 py-10">
        <x-ui.page>
            <x-ui.flash />

            <x-ui.page-header
                :title="$course->exists ? 'Modifica corso' : 'Nuovo corso'"
                subtitle="Titolo, date, stato editoriale. Nel catalogo compaiono solo i corsi Published (e dopo la data di inizio, se impostata)."
            >
                <x-slot:actions>
                    <a href="{{ route('tenant.admin.courses.index') }}" class="btn btn-ghost btn-sm">
                        &larr; Torna ai corsi
                    </a>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <form method="post"
                          enctype="multipart/form-data"
                          action="{{ $course->exists ? route('tenant.admin.courses.update', $course) : route('tenant.admin.courses.store') }}"
                          class="space-y-6">
                        @csrf
                        @if ($course->exists) @method('put') @endif

                        <div class="form-control w-full">
                            <label class="label" for="thumbnail">
                                <span class="label-text">Immagine catalogo (copertina)</span>
                            </label>
                            <p class="mb-2 text-xs text-base-content/60">Visibile nel catalogo corsi e in testa alla scheda corso. Formato immagine, max 5&nbsp;MB.</p>
                            @if ($course->exists && $course->thumbnailPublicUrl())
                                <div class="mb-3 flex flex-wrap items-center gap-4">
                                    <img src="{{ $course->thumbnailPublicUrl() }}" alt="" class="h-24 w-40 rounded-lg border border-base-300 object-cover" loading="lazy">
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
                                <label class="label" for="total_hours">
                                    <span class="label-text">Durata totale (ore, opzionale)</span>
                                </label>
                                <input id="total_hours" type="number" name="total_hours" step="0.01" min="0" class="input input-bordered w-full"
                                       value="{{ old('total_hours', $course->total_hours) }}" placeholder="es. 12">
                                @error('total_hours') <div class="mt-2 text-sm text-error">{{ $message }}</div> @enderror
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

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <div class="flex items-center gap-3">
                                <button class="btn btn-primary">
                                    Salva
                                </button>
                                @if ($course->exists)
                                    <a href="{{ route('tenant.admin.courses.builder', $course) }}" class="btn btn-outline">
                                        Moduli del corso
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if ($course->exists)
                        <form method="post" action="{{ route('tenant.admin.courses.destroy', $course) }}"
                              class="mt-6 flex justify-end"
                              onsubmit="return confirm('Eliminare il corso?')">
                            @csrf
                            @method('delete')
                            <button class="btn btn-error btn-outline">
                                Elimina
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </x-ui.page>
    </div>
</x-layouts.tenant>
