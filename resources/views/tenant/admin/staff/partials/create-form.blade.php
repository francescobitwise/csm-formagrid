<form method="post" action="{{ route('tenant.admin.staff.store') }}" class="space-y-5" id="create-staff-form">
    @csrf
    <input type="hidden" name="form_intent" value="create_staff">

    <p class="text-sm text-base-content/65">Amministratore = accesso completo. Istruttore = solo contenuti. Ispettore = report in sola lettura sui corsi assegnati.</p>

    <div class="form-control w-full">
        <label class="label" for="create_staff_name">
            <span class="label-text">Nome e cognome</span>
        </label>
        <input id="create_staff_name" name="name" value="{{ old('name') }}" type="text" class="input input-bordered w-full" required>
        @error('name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_staff_email">
            <span class="label-text">Email</span>
        </label>
        <input id="create_staff_email" name="email" value="{{ old('email') }}" type="email" class="input input-bordered w-full" required autocomplete="off">
        @error('email') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_staff_role">
            <span class="label-text">Ruolo</span>
        </label>
        <select id="create_staff_role" name="role" class="select select-bordered w-full">
            <option value="{{ \App\Enums\UserRole::Instructor->value }}" @selected(old('role', \App\Enums\UserRole::Instructor->value) === \App\Enums\UserRole::Instructor->value)>Istruttore (solo contenuti)</option>
            <option value="{{ \App\Enums\UserRole::Inspector->value }}" @selected(old('role') === \App\Enums\UserRole::Inspector->value)>Ispettore (report)</option>
            <option value="{{ \App\Enums\UserRole::Admin->value }}" @selected(old('role') === \App\Enums\UserRole::Admin->value)>Amministratore</option>
        </select>
        @error('role') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div id="create-staff-courses" class="form-control w-full @if(old('role') !== \App\Enums\UserRole::Inspector->value) hidden @endif">
        <label class="label" for="create_staff_course_ids">
            <span class="label-text">Corsi assegnati</span>
        </label>
        <select id="create_staff_course_ids" name="course_ids[]" class="select select-bordered h-40 w-full" multiple>
            @php($selectedCourses = old('course_ids', []))
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected(in_array($course->id, $selectedCourses, true))>{{ $course->title }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-base-content/60">Obbligatorio per l’ispettore. Tieni premuto Ctrl/Cmd per selezionarne più di uno.</p>
        @error('course_ids') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        @error('course_ids.*') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-control w-full">
        <label class="label" for="create_staff_password">
            <span class="label-text">Password (opzionale)</span>
        </label>
        <input id="create_staff_password" name="password" type="password" class="input input-bordered w-full" placeholder="Vuoto = generata automaticamente" autocomplete="new-password">
        @error('password') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="send_credentials_email" value="0">
        <input type="checkbox" name="send_credentials_email" value="1" class="checkbox checkbox-primary" @checked(old('send_credentials_email'))>
        Invia subito email con credenziali di accesso
    </label>
</form>

<script>
    (() => {
        const role = document.getElementById('create_staff_role');
        const courses = document.getElementById('create-staff-courses');
        if (!role || !courses) return;
        const sync = () => {
            const isInspector = role.value === @json(\App\Enums\UserRole::Inspector->value);
            courses.classList.toggle('hidden', !isInspector);
        };
        role.addEventListener('change', sync);
        sync();
    })();
</script>
