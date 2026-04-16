<x-mail::message>
# Benvenuto, {{ $userName }}!

Il tuo account su **{{ $appName }}**@if($tenantId) per l’organizzazione **{{ $tenantId }}**@endif è stato creato correttamente.

Da ora puoi accedere al catalogo corsi, iscriverti e seguire i contenuti dalla tua area personale.

<x-mail::button :url="$loginUrl">
Accedi alla piattaforma
</x-mail::button>

Se non hai creato tu questo account, contatta il supporto del tuo ente di formazione.

A presto,<br>
{{ $appName }}
</x-mail::message>
