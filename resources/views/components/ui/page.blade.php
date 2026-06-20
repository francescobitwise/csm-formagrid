@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow-xl '.$class]) }}>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
