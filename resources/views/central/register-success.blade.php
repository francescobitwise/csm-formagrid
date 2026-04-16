<x-layouts.central
    :title="config('app.name').' — Pagamento completato'"
    description="Pagamento ricevuto. L’attivazione della tua organizzazione è in corso."
>
    <div class="mx-auto w-full max-w-md px-6 py-16">
        <div class="glass-panel rounded-2xl p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-brand-amber/55 bg-brand-amber/10">
                <i class="ph ph-check-circle text-3xl text-brand-amber"></i>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white">Pagamento completato</h1>
            <p class="mt-2 text-sm text-slate-400">
                L’abbonamento è stato collegato alla tua organizzazione.
                @if ($tenantDomain)
                    Puoi accedere allo spazio dedicato all’indirizzo sotto (riceverai comunque la conferma da Stripe all’email di fatturazione).
                @else
                    Riceverai la conferma da Stripe all’email di fatturazione; l’URL del tuo spazio è il sottodominio che hai scelto in registrazione.
                @endif
            </p>
            @if ($tenantDomain)
                <p class="mt-4 break-all font-mono text-sm text-brand-blue">{{ $tenantDomain }}</p>
            @endif
            <div class="mt-8 flex flex-col items-center gap-3">
                @if ($tenantUrl)
                    <a href="{{ $tenantUrl }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-brand-blue px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        Vai al tuo spazio →
                    </a>
                    <a href="{{ route('central.home') }}"
                       class="text-sm font-medium text-slate-400 underline-offset-2 transition hover:text-slate-200 hover:underline">
                        Torna al sito principale
                    </a>
                @else
                    <a href="{{ route('central.home') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-brand-blue px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-brand-navy active:scale-95">
                        Torna al sito principale
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layouts.central>
