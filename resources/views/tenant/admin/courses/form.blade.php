<x-layouts.tenant :title="($course->exists ? 'Modifica corso' : 'Nuovo corso').' — Admin'">
    <div class="mx-auto max-w-[960px] px-6 py-10">
        @if (session('toast'))
            <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                {{ session('toast') }}
            </div>
        @endif

        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">
                    {{ $course->exists ? 'Modifica corso' : 'Nuovo corso' }}
                </h1>
                <p class="mt-1 text-sm text-slate-400">Titolo, date, stato editoriale. Nel catalogo compaiono solo i corsi <span class="text-slate-300">Published</span> (e dopo la data di inizio, se impostata).</p>
            </div>
            <a href="{{ route('tenant.admin.courses.index') }}" class="text-sm font-medium text-slate-400 hover:text-white">
                &larr; Torna ai corsi
            </a>
        </div>

        <div class="glass-panel rounded-2xl p-8">
            <form method="post"
                  enctype="multipart/form-data"
                  action="{{ $course->exists ? route('tenant.admin.courses.update', $course) : route('tenant.admin.courses.store') }}"
                  class="space-y-6">
                @csrf
                @if ($course->exists) @method('put') @endif

                <div>
                    <label class="form-label" for="thumbnail">Immagine catalogo (copertina)</label>
                    <p class="mb-2 text-xs text-slate-500">Visibile nel catalogo corsi e in testa alla scheda corso. Formato immagine, max 5&nbsp;MB.</p>
                    @if ($course->exists && $course->thumbnailPublicUrl())
                        <div class="mb-3 flex flex-wrap items-center gap-4">
                            <img src="{{ $course->thumbnailPublicUrl() }}" alt="" class="h-24 w-40 rounded-lg border border-white/10 object-cover" loading="lazy">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-400">
                                <input type="hidden" name="remove_thumbnail" value="0">
                                <input type="checkbox" name="remove_thumbnail" value="1" class="h-4 w-4 rounded border-slate-600">
                                Rimuovi copertina
                            </label>
                        </div>
                    @endif
                    <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                    @error('thumbnail') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label" for="title">Titolo</label>
                    <input id="title" name="title" class="form-input" value="{{ old('title', $course->title) }}" required>
                    @error('title') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="slug">Slug (opzionale)</label>
                        <input id="slug" name="slug" class="form-input font-mono" value="{{ old('slug', $course->slug) }}" placeholder="es. leadership-avanzata">
                        @error('slug') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="status">Stato</label>
                        <select id="status" name="status" class="form-input" required>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected(old('status', (string) ($course->status?->value ?? $course->status ?? 'draft'))===$s->value)>
                                    {{ $s->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Solo <strong class="text-slate-300">{{ \App\Enums\CourseStatus::Published->label() }}</strong> è visibile nel catalogo learner; <strong class="text-slate-300">{{ \App\Enums\CourseStatus::Draft->label() }}</strong> e <strong class="text-slate-300">{{ \App\Enums\CourseStatus::Archived->label() }}</strong> restano in admin.</p>
                        @error('status') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="form-label" for="description">Descrizione</label>
                    <textarea id="description" name="description" class="form-input h-28 resize-none" placeholder="Obiettivi e contenuti...">{{ old('description', $course->description) }}</textarea>
                    @error('description') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="starts_at">Data/ora inizio (opzionale)</label>
                        <input id="starts_at" type="datetime-local" name="starts_at" class="form-input"
                               value="{{ old('starts_at', $course->starts_at?->format('Y-m-d\TH:i')) }}">
                        <p class="mt-1 text-xs text-slate-500">Prima di questa data il corso non compare nel catalogo learner.</p>
                        @error('starts_at') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="total_hours">Durata totale (ore, opzionale)</label>
                        <input id="total_hours" type="number" name="total_hours" step="0.01" min="0" class="form-input"
                               value="{{ old('total_hours', $course->total_hours) }}" placeholder="es. 12">
                        @error('total_hours') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="assigned_company_ids">Disponibile per aziende</label>
                        <p class="mb-2 text-xs text-slate-500">Seleziona una o più aziende. Se vuoto, il corso non compare ai corsisti (a meno di assegnazione diretta).</p>
                        <select id="assigned_company_ids" name="assigned_company_ids[]" class="form-input h-40" multiple>
                            @php($selectedCompanies = old('assigned_company_ids', $assignedCompanyIds ?? []))
                            @foreach(($companies ?? []) as $company)
                                <option value="{{ $company->id }}" @selected(in_array($company->id, $selectedCompanies, true))>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_company_ids') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                        @error('assigned_company_ids.*') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="assigned_user_ids">Disponibile per corsisti specifici</label>
                        <p class="mb-2 text-xs text-slate-500">Assegnazione diretta a singoli corsisti (oltre alle aziende). Utile per eccezioni.</p>
                        <select id="assigned_user_ids" name="assigned_user_ids[]" class="form-input h-40" multiple>
                            @php($selectedLearners = old('assigned_user_ids', $assignedLearnerIds ?? []))
                            @foreach(($learners ?? []) as $learner)
                                <option value="{{ $learner->id }}" @selected(in_array($learner->id, $selectedLearners, true))>
                                    {{ $learner->name }} — {{ $learner->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_ids') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                        @error('assigned_user_ids.*') <div class="mt-2 text-sm text-rose-300">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="flex items-center gap-3">
                        <button class="rounded-xl bg-brand-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                            Salva
                        </button>
                        @if ($course->exists)
                            <a href="{{ route('tenant.admin.courses.builder', $course) }}"
                               class="rounded-xl border border-slate-700 bg-transparent px-6 py-3 text-sm font-medium text-white transition hover:bg-white/5">
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
                    <button class="rounded-xl border border-slate-700 bg-transparent px-6 py-3 text-sm font-medium text-white transition hover:bg-white/5">
                        Elimina
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.tenant>

