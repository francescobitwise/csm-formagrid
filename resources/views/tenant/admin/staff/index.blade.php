<x-layouts.tenant :title="'Staff — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Staff dell’organizzazione"
            subtitle="Amministratori e istruttori: credenziali e ruoli. Gli istruttori accedono solo ai contenuti (lezioni), non ad allievi, impostazioni organizzazione o report."
        >
            <x-slot:actions>
                <a href="{{ route('tenant.admin.staff.create') }}" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-user-plus"></i> Nuovo utente staff
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Ruolo</th>
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
                                    @else
                                        <span class="badge badge-ghost">Istruttore</span>
                                    @endif
                                </td>
                                <td class="text-base-content/70">
                                    {{ $user->credentials_sent_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
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
                                <td colspan="5" class="py-10 text-center text-base-content/60">Nessun utente staff.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $staff->links() }}
        </div>
    </x-ui.page>
</x-layouts.tenant>
