<x-layouts.tenant :title="'Staff — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Staff dell’organizzazione"
            subtitle="Amministratori, istruttori e ispettori. Gli ispettori vedono solo i report dei corsi assegnati (sola lettura e download)."
        >
            <x-slot:actions>
                <button type="button" class="btn btn-primary inline-flex items-center gap-2" data-modal-open="create-staff-modal">
                    <i class="ph ph-user-plus"></i> Nuovo utente staff
                </button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="overflow-x-auto border-b border-base-300">
            <table class="table table-zebra w-full text-sm">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Corsi</th>
                        <th>Credenziali inviate</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $user)
                        <tr>
                            <td class="font-medium">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->role === \App\Enums\UserRole::Admin)
                                    <span class="badge badge-primary">Amministratore</span>
                                @elseif ($user->role === \App\Enums\UserRole::Inspector)
                                    <span class="badge badge-secondary">Ispettore</span>
                                @else
                                    <span class="badge badge-ghost">Istruttore</span>
                                @endif
                            </td>
                            <td class="max-w-[14rem] text-base-content/70">
                                @if ($user->role === \App\Enums\UserRole::Inspector)
                                    @if ($user->inspectedCourses->isEmpty())
                                        <span class="text-warning">Nessun corso</span>
                                    @else
                                        <span title="{{ $user->inspectedCourses->pluck('title')->join(', ') }}">
                                            {{ $user->inspectedCourses->count() }}
                                            {{ $user->inspectedCourses->count() === 1 ? 'corso' : 'corsi' }}
                                        </span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-base-content/70">
                                {{ $user->credentials_sent_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    @if ($user->role === \App\Enums\UserRole::Inspector)
                                        <button type="button"
                                                class="btn btn-outline btn-xs"
                                                data-modal-open="edit-inspector-courses-{{ $user->id }}">
                                            Corsi
                                        </button>
                                    @endif
                                    <form method="post" action="{{ route('tenant.admin.staff.send-credentials', $user) }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-outline btn-xs"
                                                onclick="return confirm('Generare una nuova password e inviarla via email?');">
                                            Reinvia credenziali
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('tenant.admin.staff.destroy', $user) }}" onsubmit="return confirm('Eliminare questo utente staff?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-ghost btn-sm btn-square text-error" title="Elimina">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-base-content/60">Nessun utente staff.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 lg:px-6">
            {{ $staff->links() }}
        </div>

        <x-ui.modal id="create-staff-modal" title="Nuovo utente staff" intent="create_staff" size="md">
            @include('tenant.admin.staff.partials.create-form')
            <x-slot:footer>
                <form method="dialog" data-no-loader>
                    <button type="submit" class="btn btn-ghost">Annulla</button>
                </form>
                <button type="submit" form="create-staff-form" class="btn btn-primary">Crea utente</button>
            </x-slot:footer>
        </x-ui.modal>

        @foreach ($staff as $user)
            @if ($user->role === \App\Enums\UserRole::Inspector)
                <x-ui.modal
                    id="edit-inspector-courses-{{ $user->id }}"
                    title="Corsi di {{ $user->name }}"
                    :intent="'edit_inspector_courses_'.$user->id"
                    size="md"
                >
                    @include('tenant.admin.staff.partials.edit-courses-form', ['user' => $user, 'courses' => $courses])
                    <x-slot:footer>
                        <form method="dialog" data-no-loader>
                            <button type="submit" class="btn btn-ghost">Annulla</button>
                        </form>
                        <button type="submit" form="edit-inspector-courses-form-{{ $user->id }}" class="btn btn-primary">Salva</button>
                    </x-slot:footer>
                </x-ui.modal>
            @endif
        @endforeach
    </x-ui.page>
</x-layouts.tenant>
