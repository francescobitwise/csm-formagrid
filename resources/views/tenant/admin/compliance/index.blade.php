<x-layouts.tenant :title="'Compliance — '.tenant('id')">
    <div class="mx-auto max-w-[960px] px-6 py-10">
        <div class="admin-page-wrap">
            <div class="admin-hero mb-8">
                <h1 class="admin-title">Compliance e diritti degli interessati</h1>
                <p class="admin-subtitle">
                    Export dei dati trattati in questo LMS e registro interno delle richieste ricevute (email, PEC, altro).
                </p>
            </div>

            @if ($errors->has('export'))
                <div class="mb-6 rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ $errors->first('export') }}
                </div>
            @endif

            <div class="mb-10">
                <div class="glass-card rounded-xl border border-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">Export portability (LMS)</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Archivio ZIP con CSV degli allievi (learner) e delle iscrizioni ai corsi. Non include log di sistema né file multimediali; integrare manualmente se necessario.
                    </p>
                    <form method="post" action="{{ route('tenant.admin.compliance.export') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="admin-btn-primary inline-flex items-center gap-2">
                            <i class="ph ph-file-zip"></i> Scarica ZIP
                        </button>
                    </form>
                </div>
            </div>

            <div class="glass-card rounded-xl border border-white/5 p-6">
                <h2 class="text-lg font-semibold text-white">Registra richiesta dell’interessato</h2>
                <p class="mt-2 text-sm text-slate-400">Usa questo modulo quando ricevi una richiesta fuori piattaforma (es. email). Crea traccia interna per tempi e risposta.</p>

                <form method="post" action="{{ route('tenant.admin.compliance.privacy-requests.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="contact_email" class="mb-1 block text-xs font-medium text-slate-400">Email di contatto dell’interessato</label>
                        <input id="contact_email" name="contact_email" type="email" required value="{{ old('contact_email') }}"
                               class="w-full rounded-lg border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
                        @error('contact_email')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="request_type" class="mb-1 block text-xs font-medium text-slate-400">Tipo di richiesta</label>
                        <select id="request_type" name="request_type" required
                                class="w-full rounded-lg border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                            @foreach ($requestTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('request_type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('request_type')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="message" class="mb-1 block text-xs font-medium text-slate-400">Sintesi / riferimento</label>
                        <textarea id="message" name="message" rows="4" required minlength="5"
                                  class="w-full rounded-lg border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="admin-btn-secondary">Salva nel registro</button>
                </form>
            </div>

            <div class="mt-10">
                <h2 class="mb-4 text-lg font-semibold text-white">Ultime richieste registrate</h2>
                <div class="glass-card overflow-hidden rounded-xl border border-white/5">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/10 bg-white/5 text-xs uppercase tracking-wider text-slate-300">
                            <tr>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Contatto</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Stato</th>
                                <th class="px-4 py-3 min-w-[200px]">Aggiorna</th>
                                <th class="px-4 py-3">Registrato da</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($requests as $req)
                                <tr class="align-top hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-300">
                                        {{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-200">{{ $req->contact_email }}</td>
                                    <td class="px-4 py-3 text-slate-300">
                                        {{ \App\Enums\PrivacyRequestType::tryFrom($req->request_type)?->label() ?? $req->request_type }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($req->status === \App\Enums\PrivacyRequestStatus::InProgress)
                                            <span class="inline-flex rounded-full border border-amber-500/40 bg-amber-500/12 px-2 py-0.5 text-xs font-medium text-amber-100">{{ $req->status->label() }}</span>
                                        @elseif ($req->status === \App\Enums\PrivacyRequestStatus::Closed)
                                            <span class="inline-flex rounded-full border border-lime-500/35 bg-lime-500/10 px-2 py-0.5 text-xs font-medium text-lime-100">{{ $req->status->label() }}</span>
                                        @elseif ($req->status === \App\Enums\PrivacyRequestStatus::Rejected)
                                            <span class="inline-flex rounded-full border border-rose-500/40 bg-rose-500/10 px-2 py-0.5 text-xs font-medium text-rose-100">{{ $req->status->label() }}</span>
                                        @else
                                            <span class="inline-flex rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-xs font-medium text-slate-200">{{ $req->status->label() }}</span>
                                        @endif
                                        @if ($req->status_updated_at)
                                            <div class="mt-1 text-[11px] text-slate-500">Stato agg.: {{ $req->status_updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="post" action="{{ route('tenant.admin.compliance.privacy-requests.update', $req) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="min-w-[10rem] flex-1 rounded-lg border border-white/15 bg-slate-950 px-2 py-1.5 text-xs text-slate-100">
                                                @foreach ($privacyStatuses as $ps)
                                                    <option value="{{ $ps->value }}" @selected($req->status === $ps)>{{ $ps->label() }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="shrink-0 rounded-lg border border-brand-blue/40 bg-brand-blue/15 px-2 py-1.5 text-xs font-semibold text-brand-blue hover:bg-brand-blue/25">Salva</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-400">
                                        {{ $req->recordedBy?->email ?? '—' }}
                                    </td>
                                </tr>
                                <tr class="border-b border-white/5 bg-black/20">
                                    <td colspan="6" class="px-4 py-3 text-xs text-slate-500">
                                        {{ \Illuminate\Support\Str::limit($req->message, 280) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">Nessuna richiesta registrata.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
