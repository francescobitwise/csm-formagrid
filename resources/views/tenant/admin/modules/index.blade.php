<x-layouts.tenant :title="'Moduli — Admin'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="admin-page-wrap">
        @if (session('toast'))
            <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                {{ session('toast') }}
            </div>
        @endif


        <div class="glass-card mb-6 rounded-xl border border-white/5 p-4">
            <form class="flex flex-col gap-4 sm:flex-row sm:items-center" method="get" action="{{ route('tenant.admin.modules.index') }}">
                <div class="relative flex-1">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-500"></i>
                    <input name="q" value="{{ $q }}" class="form-input pl-10" placeholder="Cerca per titolo...">
                </div>

                <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
                    Cerca
                </button>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-xl border border-white/5">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-slate-900/80">
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Modulo</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Corsi</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Lezioni</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($modules as $module)
                            <tr class="group transition-colors hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium text-white">{{ $module->title }}</span>
                                        @php($dur = $moduleDurations[$module->getKey()] ?? ['total_seconds' => 0, 'lesson_count_with_duration' => 0])
                                        @if (($dur['lesson_count_with_duration'] ?? 0) > 0)
                                            <span class="inline-flex items-center gap-1 rounded border border-brand-amber/45 bg-brand-amber/10 px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-brand-amber"
                                                  title="Durata totale (somma delle lezioni con durata indicata)">
                                                <i class="ph ph-timer text-[13px] text-brand-amber/90" aria-hidden="true"></i>
                                                {{ \App\Support\DurationFormat::secondsToMmss($dur['total_seconds']) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded border border-white/10 bg-white/[0.03] px-2 py-0.5 text-[11px] font-medium text-slate-500"
                                                  title="Nessuna durata indicata sulle lezioni">
                                                —
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-300">
                                    @if ($module->courses->isEmpty())
                                        <span class="text-slate-500">—</span>
                                    @else
                                        <span class="inline-flex flex-wrap gap-1">
                                            @foreach ($module->courses as $c)
                                                @tenantcan('content.courses.manage')
                                                    <a href="{{ route('tenant.admin.courses.builder', $c) }}"
                                                       class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-xs text-slate-200 transition hover:border-brand-blue/35 hover:text-white">
                                                        {{ $c->title }}
                                                    </a>
                                                @else
                                                    <span class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-xs text-slate-300">{{ $c->title }}</span>
                                                @endtenantcan
                                            @endforeach
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-400">{{ $module->lessons_count }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                        @tenantcan('content.lessons')
                                            <a href="{{ route('tenant.admin.modules.lessons', $module) }}"
                                               class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-brand-amber"
                                               title="Lezioni">
                                                <i class="ph ph-list-numbers text-lg"></i>
                                            </a>
                                        @endtenantcan
                                        @tenantcan('content.modules.manage')
                                            <a href="{{ route('tenant.admin.modules.edit', $module) }}"
                                               class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-white"
                                               title="Modifica modulo">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                        @endtenantcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-10 text-sm text-slate-400" colspan="4">
                                    Nessun modulo: creane uno con «Nuovo modulo».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-white/5 bg-slate-900/30 px-6 py-4">
                {{ $modules->links() }}
            </div>
        </div>
        </div>
    </div>
</x-layouts.tenant>
