<x-layouts.tenant :title="'Corsi — Admin'">
    <div class="mx-auto max-w-[1440px] px-6 py-10">
        <div class="admin-page-wrap">
        @if (session('toast'))
            <div class="mb-6 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                {{ session('toast') }}
            </div>
        @endif

        <div class="glass-card mb-6 rounded-xl border border-white/5 p-4">
            <form class="flex flex-col gap-4 sm:flex-row sm:items-center" method="get" action="{{ route('tenant.admin.courses.index') }}">
                <div class="relative flex-1">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-500"></i>
                    <input name="q" value="{{ $q }}" class="form-input pl-10" placeholder="Cerca corso per titolo...">
                </div>

                <div class="flex gap-3">
                    <select name="status" class="form-input w-44">
                        <option value="">Tutti gli stati</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected($status===$s->value)>{{ $s->label() }}</option>
                            @endforeach
                    </select>

                    <button class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
                        Filtra
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-xl border border-white/5">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-slate-900/80">
                            <th class="w-16 px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400"></th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Titolo</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Slug</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Stato</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($courses as $course)
                            <tr class="group transition-colors hover:bg-white/[0.02]">
                                <td class="px-4 py-3">
                                    @if ($u = $course->thumbnailPublicUrl())
                                        <img src="{{ $u }}" alt="" class="h-10 w-14 rounded-md border border-white/10 object-cover">
                                    @else
                                        <div class="flex h-10 w-14 items-center justify-center rounded-md border border-dashed border-white/15 bg-white/5 text-slate-600">
                                            <i class="ph ph-image text-lg" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-white">{{ $course->title }}</div>
                                    @if ($course->description)
                                        <div class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $course->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ $course->slug }}</td>
                                <td class="px-6 py-4">
                                    @php($v = (string) ($course->status?->value ?? $course->status))
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium
                                        @if($v==='published') border-lime-500/20 bg-lime-500/10 text-lime-400
                                        @elseif($v==='draft') border-amber-500/20 bg-amber-500/10 text-amber-400
                                        @else border-slate-500/20 bg-slate-500/10 text-slate-400 @endif">
                                        {{ \App\Enums\CourseStatus::tryFrom($v)?->label() ?? $v }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                        @tenantcan('content.courses.read')
                                            <a href="{{ route('tenant.admin.courses.learners', $course) }}"
                                               class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-brand-amber"
                                               title="Corsisti, minuti visti e completamento">
                                                <i class="ph ph-chart-line-up text-lg"></i>
                                            </a>
                                        @endtenantcan
                                        @tenantcan('content.courses.manage')
                                            <a href="{{ route('tenant.admin.courses.builder', $course) }}"
                                               class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-white"
                                               title="Moduli del corso">
                                                <i class="ph ph-squares-four text-lg"></i>
                                            </a>
                                            <a href="{{ route('tenant.admin.courses.edit', $course) }}"
                                               class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-brand-amber">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                            <form method="post" action="{{ route('tenant.admin.courses.destroy', $course) }}"
                                                  onsubmit="return confirm('Eliminare il corso?')">
                                                @csrf
                                                @method('delete')
                                                <button class="rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-rose-400">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        @endtenantcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-10 text-sm text-slate-400" colspan="5">
                                    Nessun corso trovato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-white/5 bg-slate-900/30 px-6 py-4">
                {{ $courses->links() }}
            </div>
        </div>
        </div>
    </div>
</x-layouts.tenant>

