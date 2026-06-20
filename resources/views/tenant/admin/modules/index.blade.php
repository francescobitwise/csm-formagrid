<x-layouts.tenant :title="'Moduli — Admin'">
    <x-ui.page>
        <div class="card bg-base-100 shadow-lg mb-6">
            <div class="card-body p-4">
                <form class="flex flex-col gap-4 sm:flex-row sm:items-center" method="get" action="{{ route('tenant.admin.modules.index') }}">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-lg text-base-content/50"></i>
                        <input name="q" value="{{ $q }}" class="input input-bordered w-full pl-10" placeholder="Cerca per titolo...">
                    </div>

                    <button type="submit" class="btn btn-outline">
                        Cerca
                    </button>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Modulo</th>
                            <th>Corsi</th>
                            <th>Lezioni</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($modules as $module)
                            <tr class="group">
                                <td>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium">{{ $module->title }}</span>
                                        @php($dur = $moduleDurations[$module->getKey()] ?? ['total_seconds' => 0, 'lesson_count_with_duration' => 0])
                                        @if (($dur['lesson_count_with_duration'] ?? 0) > 0)
                                            <span class="badge badge-warning font-mono tabular-nums"
                                                  title="Durata totale (somma delle lezioni con durata indicata)">
                                                <i class="ph ph-timer text-[13px]" aria-hidden="true"></i>
                                                {{ \App\Support\DurationFormat::secondsToMmss($dur['total_seconds']) }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost"
                                                  title="Nessuna durata indicata sulle lezioni">
                                                —
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-sm">
                                    @if ($module->courses->isEmpty())
                                        <span class="text-base-content/60">—</span>
                                    @else
                                        <span class="inline-flex flex-wrap gap-1">
                                            @foreach ($module->courses as $c)
                                                @tenantcan('content.courses.manage')
                                                    <a href="{{ route('tenant.admin.courses.builder', $c) }}"
                                                       class="badge badge-outline hover:badge-primary">
                                                        {{ $c->title }}
                                                    </a>
                                                @else
                                                    <span class="badge badge-ghost">{{ $c->title }}</span>
                                                @endtenantcan
                                            @endforeach
                                        </span>
                                    @endif
                                </td>
                                <td class="text-base-content/70">{{ $module->lessons_count }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        @tenantcan('content.lessons')
                                            <a href="{{ route('tenant.admin.modules.lessons', $module) }}"
                                               class="btn btn-ghost btn-sm btn-square"
                                               title="Lezioni">
                                                <i class="ph ph-list-numbers text-lg"></i>
                                            </a>
                                        @endtenantcan
                                        @tenantcan('content.modules.manage')
                                            <a href="{{ route('tenant.admin.modules.edit', $module) }}"
                                               class="btn btn-ghost btn-sm btn-square"
                                               title="Modifica modulo">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                        @endtenantcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-10 text-sm text-base-content/70" colspan="4">
                                    Nessun modulo: creane uno con «Nuovo modulo».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-base-300 px-6 py-4">
                {{ $modules->links() }}
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
