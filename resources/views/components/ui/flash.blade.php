@if (session('toast'))
    <x-ui.alert type="warning" dismiss="3000" class="mb-6">
        {{ session('toast') }}
    </x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert type="error" class="mb-6">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @endif
    </x-ui.alert>
@endif
