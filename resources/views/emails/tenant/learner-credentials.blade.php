<x-mail::message>
# Le tue credenziali di accesso

Ciao **{{ $userName }}**,

è stato creato un account **{{ $appName }}**@if($tenantId) per l’organizzazione **{{ $tenantId }}**@endif. Usa i dati qui sotto per accedere (puoi cambiare la password dopo il login da «Password dimenticata» o chiedendo al tuo referente).

<x-mail::panel>
**URL:** [{{ $loginUrl }}]({{ $loginUrl }})<br>
**Email:** {{ $email }}<br>
**Password temporanea:** `{{ $plainPassword }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Accedi ora
</x-mail::button>

Per sicurezza, non inoltrare questa email e non condividere la password.

{{ $appName }}
</x-mail::message>
