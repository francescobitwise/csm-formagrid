@props([
    'id' => 'create-modal',
    'title',
    'open' => false,
    'intent' => null,
    'size' => 'md', // sm | md | lg | xl
])

@php
    $sizeClass = match ($size) {
        'sm' => 'max-w-md',
        'lg' => 'max-w-3xl',
        'xl' => 'max-w-5xl',
        default => 'max-w-xl',
    };
    $shouldOpen = filter_var($open, FILTER_VALIDATE_BOOLEAN)
        || ($intent !== null && old('form_intent') === $intent)
        || (request()->boolean('create') && ($intent === null || ! old('form_intent') || old('form_intent') === $intent));
@endphp

<dialog
    id="{{ $id }}"
    class="modal"
    data-ui-modal
    @if ($shouldOpen) data-open-on-load="1" @endif
>
    <div class="modal-box flex max-h-[85vh] w-11/12 flex-col p-0 {{ $sizeClass }}">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-base-300 px-5 py-4">
            <h3 class="text-lg font-semibold text-base-content">{{ $title }}</h3>
            <form method="dialog" data-no-loader>
                <button type="submit" class="btn btn-ghost btn-sm btn-square" aria-label="Chiudi">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </form>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-base-300 px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
    <form method="dialog" class="modal-backdrop" data-no-loader>
        <button type="submit" aria-label="Chiudi">close</button>
    </form>
</dialog>
