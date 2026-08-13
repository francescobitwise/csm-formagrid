<x-layouts.tenant :title="'Registro attività — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Registro attività staff"
            subtitle="Traccia delle azioni amministrative (creazione, modifica, eliminazione e download sensibili). I campi sensibili non vengono memorizzati."
        />

        <div class="border-b border-base-300 bg-base-200/40 px-4 py-2 lg:px-6">
            <form method="get" action="{{ route('tenant.admin.audit-log.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="form-control min-w-[200px]">
                    <label for="user_id" class="label py-1">
                        <span class="label-text text-xs text-base-content/70">Utente staff</span>
                    </label>
                    <select id="user_id" name="user_id" class="select select-bordered select-sm w-full">
                        <option value="">Tutti</option>
                        @foreach ($staffUsers as $u)
                            <option value="{{ $u->id }}" @selected($filterUserId === $u->id)>{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-sm">Filtra</button>
                @if ($filterUserId !== '')
                    <a href="{{ route('tenant.admin.audit-log.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto border-b border-base-300">
            <table class="table table-zebra w-full text-sm">
                <thead>
                    <tr>
                        <th>Data (UTC)</th>
                        <th>Utente</th>
                        <th>Metodo</th>
                        <th>Rotta</th>
                        <th>HTTP</th>
                        <th>Dettagli</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="whitespace-nowrap text-base-content/70">
                                {{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}
                            </td>
                            <td>
                                @if ($log->user)
                                    <div class="font-medium">{{ $log->user->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-base-content/60">—</span>
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $log->http_method }}</td>
                            <td>
                                <div class="font-mono text-xs text-primary">{{ $log->route_name ?? '—' }}</div>
                                <div class="mt-0.5 break-all text-xs text-base-content/60">/{{ $log->path }}</div>
                            </td>
                            <td>{{ $log->response_status ?? '—' }}</td>
                            <td class="text-xs text-base-content/70">
                                @if ($log->metadata)
                                    <details class="cursor-pointer">
                                        <summary class="link link-primary text-xs">Payload sanitizzato</summary>
                                        <pre class="mt-2 max-h-40 overflow-auto rounded-lg bg-base-200 p-2 text-[11px]">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-base-content/60">Nessuna voce registrata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 lg:px-6">
            {{ $logs->links() }}
        </div>
    </x-ui.page>
</x-layouts.tenant>
