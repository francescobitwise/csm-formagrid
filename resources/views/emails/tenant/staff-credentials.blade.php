<x-mail::message>
# Accesso allo staff

Ciao **{{ $userName }}**,

ti è stato creato un account **{{ $roleLabel }}** su **{{ $appName }}**@if($tenantId) (organizzazione: **{{ $tenantId }}**)@endif.

<x-mail::panel>
**URL:** [{{ $loginUrl }}]({{ $loginUrl }})<br>
**Email:** {{ $email }}<br>
**Password temporanea (copia senza spazi):** `{{ $plainPassword }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Accedi all’area admin
</x-mail::button>

Dopo il login potrai operare in base al tuo ruolo. Non inoltrare questa email.

{{ $appName }}
</x-mail::message>
