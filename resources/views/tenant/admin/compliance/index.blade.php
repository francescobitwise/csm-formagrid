<x-layouts.tenant :title="'Compliance — '.tenant('id')">
    <x-ui.page>
        <x-ui.page-header
            title="Compliance e diritti degli interessati"
            subtitle="Export dei dati trattati in questo LMS e registro interno delle richieste ricevute (email, PEC, altro)."
        />

        <section class="border-b border-base-300 px-4 py-6 lg:px-6">
            <h2 class="text-lg font-semibold">Export portability (LMS)</h2>
            <p class="mt-2 max-w-3xl text-sm text-base-content/65">
                Archivio ZIP con CSV degli allievi (learner) e delle iscrizioni ai corsi. Non include log di sistema né file multimediali; integrare manualmente se necessario.
            </p>
            <form method="post" action="{{ route('tenant.admin.compliance.export') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="ph ph-file-zip"></i> Scarica ZIP
                </button>
            </form>
        </section>

        <section class="border-b border-base-300 px-4 py-6 lg:px-6">
            <h2 class="text-lg font-semibold">Registra richiesta dell’interessato</h2>
            <p class="mt-2 max-w-3xl text-sm text-base-content/65">Usa questo modulo quando ricevi una richiesta fuori piattaforma (es. email). Crea traccia interna per tempi e risposta.</p>

            <form method="post" action="{{ route('tenant.admin.compliance.privacy-requests.store') }}" class="mt-6 max-w-xl space-y-4">
                @csrf
                <div class="form-control w-full">
                    <label for="contact_email" class="label py-1">
                        <span class="label-text text-xs text-base-content/70">Email di contatto dell’interessato</span>
                    </label>
                    <input id="contact_email" name="contact_email" type="email" required value="{{ old('contact_email') }}"
                           class="input input-bordered w-full" />
                    @error('contact_email')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-control w-full">
                    <label for="request_type" class="label py-1">
                        <span class="label-text text-xs text-base-content/70">Tipo di richiesta</span>
                    </label>
                    <select id="request_type" name="request_type" required class="select select-bordered w-full">
                        @foreach ($requestTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('request_type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('request_type')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-control w-full">
                    <label for="message" class="label py-1">
                        <span class="label-text text-xs text-base-content/70">Sintesi / riferimento</span>
                    </label>
                    <textarea id="message" name="message" rows="4" required minlength="5"
                              class="textarea textarea-bordered w-full">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-outline">Salva nel registro</button>
            </form>
        </section>

        <section>
            <div class="border-b border-base-300 px-4 py-4 lg:px-6">
                <h2 class="text-lg font-semibold">Ultime richieste registrate</h2>
            </div>
            <div class="overflow-x-auto border-b border-base-300">
                <table class="table table-zebra w-full text-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Contatto</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th class="min-w-[200px]">Aggiorna</th>
                            <th>Registrato da</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr class="align-top">
                                <td class="whitespace-nowrap text-base-content/70">
                                    {{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td>{{ $req->contact_email }}</td>
                                <td class="text-base-content/70">
                                    {{ \App\Enums\PrivacyRequestType::tryFrom($req->request_type)?->label() ?? $req->request_type }}
                                </td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'badge-warning' => $req->status === \App\Enums\PrivacyRequestStatus::InProgress,
                                        'badge-success' => $req->status === \App\Enums\PrivacyRequestStatus::Closed,
                                        'badge-error' => $req->status === \App\Enums\PrivacyRequestStatus::Rejected,
                                        'badge-ghost' => ! in_array($req->status, [
                                            \App\Enums\PrivacyRequestStatus::InProgress,
                                            \App\Enums\PrivacyRequestStatus::Closed,
                                            \App\Enums\PrivacyRequestStatus::Rejected,
                                        ], true),
                                    ])>{{ $req->status->label() }}</span>
                                    @if ($req->status_updated_at)
                                        <div class="mt-1 text-[11px] text-base-content/60">Stato agg.: {{ $req->status_updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('tenant.admin.compliance.privacy-requests.update', $req) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="select select-bordered select-sm min-w-[10rem] flex-1">
                                            @foreach ($privacyStatuses as $ps)
                                                <option value="{{ $ps->value }}" @selected($req->status === $ps)>{{ $ps->label() }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm shrink-0">Salva</button>
                                    </form>
                                </td>
                                <td class="text-xs text-base-content/70">
                                    {{ $req->recordedBy?->email ?? '—' }}
                                </td>
                            </tr>
                            <tr class="bg-base-200/50">
                                <td colspan="6" class="text-xs text-base-content/60">
                                    {{ \Illuminate\Support\Str::limit($req->message, 280) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-base-content/60">Nessuna richiesta registrata.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-ui.page>
</x-layouts.tenant>
