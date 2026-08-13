@props(['class' => ''])

<div {{ $attributes->merge(['class' => trim('w-full '.$class)]) }}>
    {{ $slot }}
</div>
