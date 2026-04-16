<x-layouts.tenant :title="'Registro attività — '.tenant('id')">
    <div class="mx-auto max-w-[1200px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="admin-hero mb-8">
                <h1 class="admin-title">Registro attività staff</h1>
                <p class="admin-subtitle max-w-3xl">
                    Traccia delle azioni amministrative (creazione, modifica, eliminazione e download sensibili). I campi sensibili non vengono memorizzati.
                </p>
            </div>

            <form method="get" action="{{ route('tenant.admin.audit-log.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
                <div class="min-w-[200px]">
                    <label for="user_id" class="mb-1 block text-xs font-medium text-slate-400">Utente staff</label>
                    <select id="user_id" name="user_id"
                            class="w-full rounded-lg border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Tutti</option>
                        @foreach ($staffUsers as $u)
                            <option value="{{ $u->id }}" @selected($filterUserId === $u->id)>{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="admin-btn-secondary">Filtra</button>
                @if ($filterUserId !== '')
                    <a href="{{ route('tenant.admin.audit-log.index') }}" class="rounded-lg border border-white/15 px-3 py-2 text-sm text-slate-300 hover:bg-white/5">Reset</a>
                @endif
            </form>

            <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-white/5 text-xs uppercase tracking-wider text-slate-300">
                        <tr>
                            <th class="px-4 py-3">Data (UTC)</th>
                            <th class="px-4 py-3">Utente</th>
                            <th class="px-4 py-3">Metodo</th>
                            <th class="px-4 py-3">Rotta</th>
                            <th class="px-4 py-3">HTTP</th>
                            <th class="px-4 py-3">Dettagli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($logs as $log)
                            <tr class="align-top hover:bg-white/[0.02]">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-300">
                                    {{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-200">
                                    @if ($log->user)
                                        <div class="font-medium">{{ $log->user->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ $log->http_method }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-mono text-xs text-brand-blue/90">{{ $log->route_name ?? '—' }}</div>
                                    <div class="mt-0.5 break-all text-xs text-slate-500">/{{ $log->path }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ $log->response_status ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-400">
                                    @if ($log->metadata)
                                        <details class="cursor-pointer">
                                            <summary class="text-brand-blue/80 hover:text-brand-blue">Payload sanitizzato</summary>
                                            <pre class="mt-2 max-h-40 overflow-auto rounded-lg bg-black/30 p-2 text-[11px] text-slate-300">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500">Nessuna voce registrata.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-layouts.tenant>
