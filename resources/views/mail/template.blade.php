<x-mail::message>
# Synthèse de la demande

**Question posée :**  
{{ $message }}


<x-mail::button :url="config('app.url')">
Accéder à la plateforme
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>