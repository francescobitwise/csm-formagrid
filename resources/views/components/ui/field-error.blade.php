@props(['message'])

@if ($message)
    <label class="label py-1">
        <span class="label-text-alt text-error">{{ $message }}</span>
    </label>
@endif
