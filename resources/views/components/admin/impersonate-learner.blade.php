@props(['user' => null])

@tenantcan('learners.manage')
    @if ($user && $user->isLearner())
        <form method="post" action="{{ route('tenant.admin.learners.impersonate', $user) }}" class="inline">
            @csrf
            <button type="submit"
                    {{ $attributes->merge(['class' => 'btn btn-ghost btn-xs inline-flex items-center gap-1']) }}
                    onclick="return confirm(@js('Entrare come '.$user->displayName().'? Vedrai i suoi corsi. I progressi registrati da qui sono i suoi.'))">
                <i class="ph ph-sign-in text-sm"></i>
                Entra come allievo
            </button>
        </form>
    @endif
@endtenantcan
