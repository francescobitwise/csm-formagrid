<x-layouts.tenant :title="'Corsi — Admin'">
    <x-ui.page>
        <div class="card bg-base-100 shadow-lg mb-6">
            <div class="card-body p-4">
                <form class="flex flex-col gap-4 sm:flex-row sm:items-center" method="get" action="{{ route('tenant.admin.courses.index') }}">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-lg text-base-content/50"></i>
                        <input name="q" value="{{ $q }}" class="input input-bordered w-full pl-10" placeholder="Cerca corso per titolo...">
                    </div>

                    <div class="flex gap-3">
                        <select name="status" class="input input-bordered w-44">
                            <option value="">Tutti gli stati</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected($status===$s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-outline">
                            Filtra
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th class="w-16"></th>
                            <th>Titolo</th>
                            <th>Slug</th>
                            <th>Stato</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($courses as $course)
                            <tr class="group">
                                <td>
                                    @if ($u = $course->thumbnailPublicUrl())
                                        <img src="{{ $u }}" alt="" class="h-10 w-14 rounded-md border border-base-300 object-cover">
                                    @else
                                        <div class="flex h-10 w-14 items-center justify-center rounded-md border border-dashed border-base-300 bg-base-200 text-base-content/40">
                                            <i class="ph ph-image text-lg" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-sm font-medium">{{ $course->title }}</div>
                                    @if ($course->description)
                                        <div class="mt-1 line-clamp-1 text-xs text-base-content/60">{{ $course->description }}</div>
                                    @endif
                                </td>
                                <td class="font-mono text-xs text-base-content/70">{{ $course->slug }}</td>
                                <td>
                                    @php($v = (string) ($course->status?->value ?? $course->status))
                                    <span @class([
                                        'badge',
                                        'badge-success' => $v === 'published',
                                        'badge-warning' => $v === 'draft',
                                        'badge-ghost' => ! in_array($v, ['published', 'draft'], true),
                                    ])>
                                        {{ \App\Enums\CourseStatus::tryFrom($v)?->label() ?? $v }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        @tenantcan('content.courses.read')
                                            <a href="{{ route('tenant.admin.courses.learners', $course) }}"
                                               class="btn btn-ghost btn-sm btn-square"
                                               title="Corsisti, minuti visti e completamento">
                                                <i class="ph ph-chart-line-up text-lg"></i>
                                            </a>
                                        @endtenantcan
                                        @tenantcan('content.courses.manage')
                                            <a href="{{ route('tenant.admin.courses.builder', $course) }}"
                                               class="btn btn-ghost btn-sm btn-square"
                                               title="Moduli del corso">
                                                <i class="ph ph-squares-four text-lg"></i>
                                            </a>
                                            <a href="{{ route('tenant.admin.courses.edit', $course) }}"
                                               class="btn btn-ghost btn-sm btn-square">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                            <form method="post" action="{{ route('tenant.admin.courses.destroy', $course) }}"
                                                  onsubmit="return confirm('Eliminare il corso?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-ghost btn-sm btn-square text-error">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        @endtenantcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-10 text-sm text-base-content/70" colspan="5">
                                    Nessun corso trovato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-base-300 px-6 py-4">
                {{ $courses->links() }}
            </div>
        </div>
    </x-ui.page>
</x-layouts.tenant>
