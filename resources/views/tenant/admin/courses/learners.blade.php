<x-layouts.tenant :title="$course->title.' — Corsisti'">
    <x-ui.page>
        <x-ui.page-header :title="$course->title">
            <x-slot:breadcrumb>
                <a href="{{ route('tenant.admin.courses.index') }}" class="link link-hover">Corsi</a>
                <span aria-hidden="true">/</span>
                <span class="text-base-content/80">Corsisti</span>
            </x-slot:breadcrumb>
            <x-slot:subtitle>
                <p class="text-sm text-base-content/65">
                    Corsisti iscritti: minuti visti = somma sessioni (<span class="font-mono">watch_time_sessions</span>), completamento da progressi lezione. “Sta guardando” = attività (video o SCORM) negli ultimi {{ (int) $activeWithinSeconds }}s.
                </p>
            </x-slot:subtitle>
            <x-slot:actions>
                <a href="{{ route('tenant.admin.courses.companies-report', $course) }}"
                   class="btn btn-outline btn-sm inline-flex items-center gap-2">
                    <i class="ph ph-buildings text-base"></i>
                    Report aziende
                </a>
                <a href="{{ route('tenant.admin.courses.learners.pdf', $course) }}"
                   data-no-loader
                   class="btn btn-outline btn-sm inline-flex items-center gap-2">
                    <i class="ph ph-file-pdf text-base"></i>
                    Esporta PDF ore corso
                </a>
                @php($v = (string) ($course->status?->value ?? $course->status))
                <span @class([
                    'badge',
                    'badge-success' => $v === 'published',
                    'badge-warning' => $v === 'draft',
                    'badge-ghost' => ! in_array($v, ['published', 'draft'], true),
                ])>
                    {{ \App\Enums\CourseStatus::tryFrom($v)?->label() ?? $v }}
                </span>
                <span class="font-mono text-xs text-base-content/70">{{ $course->slug }}</span>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="overflow-x-auto border-b border-base-300">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Corsista</th>
                        <th>Minuti visti</th>
                        <th>Completamento</th>
                        <th>Stato</th>
                        <th class="text-right">Ultima attività</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>
                                <div class="text-sm font-medium">{{ $enrollment->user?->name ?? '—' }}</div>
                                <div class="mt-1 text-xs text-base-content/60">{{ $enrollment->user?->email ?? '' }}</div>
                                <div class="mt-2">
                                    <a class="link link-primary text-xs"
                                       href="{{ route('tenant.admin.courses.learners.time', [$course, $enrollment]) }}">
                                        Dettaglio tempi &rarr;
                                    </a>
                                    <span class="text-base-content/50"> · </span>
                                    <a class="link link-hover text-xs"
                                       data-no-loader
                                       href="{{ route('tenant.admin.courses.learners.report.pdf', [$course, $enrollment]) }}">
                                        PDF report &rarr;
                                    </a>
                                </div>
                                @if ($enrollment->is_watching_now)
                                    <div class="mt-2">
                                        <span class="badge badge-success gap-1">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-success"></span>
                                            Sta guardando
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm">{{ (int) ($enrollment->minutes_watched ?? 0) }}</div>
                                <div class="mt-1 text-xs text-base-content/60">min</div>
                            </td>
                            <td>
                                @php($pct = (float) ($enrollment->progress_pct ?? 0))
                                @php($pctClamped = (int) min(100, max(0, $pct)))
                                <div class="flex items-center gap-3">
                                    <progress class="progress progress-primary w-40" value="{{ $pctClamped }}" max="100"></progress>
                                    <div class="text-sm">{{ number_format($pct, 0) }}%</div>
                                </div>
                            </td>
                            <td>
                                @php($s = (string) ($enrollment->status?->value ?? $enrollment->status))
                                <span @class([
                                    'badge',
                                    'badge-success' => $s === 'completed',
                                    'badge-info' => $s === 'active',
                                    'badge-ghost' => ! in_array($s, ['completed', 'active'], true),
                                ])>
                                    {{ $s }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if ($enrollment->last_activity_at)
                                    <div class="text-sm">{{ $enrollment->last_activity_at->format('d/m/Y H:i') }}</div>
                                    <div class="mt-1 text-xs text-base-content/60">{{ $enrollment->last_activity_at->diffForHumans() }}</div>
                                @else
                                    <div class="text-sm text-base-content/60">—</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-10 text-base-content/70" colspan="5">
                                Nessun corsista iscritto a questo corso.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 lg:px-6">
            {{ $enrollments->links() }}
        </div>
    </x-ui.page>
</x-layouts.tenant>
