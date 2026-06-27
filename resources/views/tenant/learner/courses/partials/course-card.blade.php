@props([
    'course',
    'showEnrolledBadge' => false,
])

<a href="{{ route('tenant.courses.show', $course) }}"
   class="card bordered bg-base-100 group overflow-hidden transition hover:shadow-md">
    <figure class="relative aspect-[16/10] w-full overflow-hidden bg-base-300">
        @if ($url = $course->thumbnailPublicUrl())
            <img src="{{ $url }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
        @else
            <div class="flex h-full w-full items-center justify-center text-base-content/30">
                <i class="ph ph-image text-5xl opacity-40" aria-hidden="true"></i>
            </div>
        @endif
    </figure>
    <div class="card-body flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs uppercase tracking-wider text-base-content/60">Corso</span>
            @if ($showEnrolledBadge && ($course->user_enrolled ?? false))
                <span class="badge badge-warning badge-sm">Iscritto</span>
            @endif
        </div>
        <h2 class="card-title mt-2 text-lg group-hover:text-primary">{{ $course->title }}</h2>
        @if (filled($course->description))
            <p class="line-clamp-2 flex-1 text-sm text-base-content/70">{{ $course->description }}</p>
        @endif
        <div class="mt-4 flex items-center gap-4 text-xs text-base-content/60">
            <span>{{ (int) ($course->modules_count ?? 0) }} {{ ((int) ($course->modules_count ?? 0)) === 1 ? 'modulo' : 'moduli' }}</span>
            <span>{{ (int) ($course->lessons_count ?? 0) }} {{ ((int) ($course->lessons_count ?? 0)) === 1 ? 'lezione' : 'lezioni' }}</span>
        </div>
    </div>
</a>
