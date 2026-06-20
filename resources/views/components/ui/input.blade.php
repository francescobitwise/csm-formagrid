@props(['label' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->only('class')->merge(['class' => 'form-control w-full']) }}>
    @if ($label)
        <label class="label">
            <span class="label-text font-medium">{{ $label }}</span>
        </label>
    @endif
    <input {{ $attributes->except('class')->merge(['class' => 'input input-bordered w-full']) }} />
    @if ($error)
        <x-ui.field-error :message="$error" />
    @endif
    @if ($hint)
        <label class="label">
            <span class="label-text-alt text-base-content/60">{{ $hint }}</span>
        </label>
    @endif
</div>
