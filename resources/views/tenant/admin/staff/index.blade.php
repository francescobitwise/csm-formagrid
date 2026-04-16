<x-layouts.tenant :title="'Staff — '.tenant('id')">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="admin-hero mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="admin-title">Staff dell’organizzazione</h1>
                    <p class="admin-subtitle">Amministratori e istruttori: credenziali e ruoli. Gli istruttori accedono solo ai contenuti (lezioni), non ad allievi, impostazioni organizzazione o report.</p>
                </div>
                <a href="{{ route('tenant.admin.staff.create') }}" class="admin-btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-user-plus"></i> Nuovo utente staff
                </a>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    @foreach ($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-white/5 text-xs uppercase tracking-wider text-slate-300">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Ruolo</th>
                            <th class="px-6 py-3">Credenziali inviate</th>
                            <th class="px-6 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($staff as $user)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-6 py-4 font-medium text-slate-100">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-slate-200">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    @if ($user->role === \App\Enums\UserRole::Admin)
                                        <span class="rounded-full border border-brand-blue/30 bg-brand-blue/10 px-2 py-0.5 text-xs font-semibold text-slate-100">Amministratore</span>
                                    @else
                                        <span class="rounded-full border border-brand-navy/35 bg-brand-navy/10 px-2 py-0.5 text-xs font-semibold text-slate-100">Istruttore</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $user->credentials_sent_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="post" action="{{ route('tenant.admin.staff.send-credentials', $user) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="rounded-lg border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/10"
                                                    onclick="return confirm('Generare una nuova password e inviarla via email?');">
                                                Reinvia credenziali
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('tenant.admin.staff.destroy', $user) }}" onsubmit="return confirm('Eliminare questo utente staff?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-rose-400" title="Elimina">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">Nessun utente staff.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $staff->links() }}
            </div>
        </div>
    </div>
</x-layouts.tenant>
