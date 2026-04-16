<x-mail::message>
# Reimposta la password

Ciao **{{ $userName }}**,

Hai ricevuto questa email perché è stata richiesta una nuova password per il tuo account su **{{ $appName }}**@if($tenantId) (organizzazione: `{{ $tenantId }}`)@endif.

<x-mail::button :url="$url">
Reimposta password
</x-mail::button>

Il link è valido per **{{ $expire }} minuti**. Se non hai richiesto tu il reset, ignora questo messaggio: la password resta invariata.

Saluti,<br>
{{ $appName }}
</x-mail::message>
