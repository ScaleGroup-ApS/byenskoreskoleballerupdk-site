<x-mail::message>
# Velkommen til Køreskolen, {{ $name }}!

En profil er netop blevet oprettet til dig. 
Du kan logge ind sikkert i systemet uden adgangskode ved blot at klikke på knappen nedenfor:

<x-mail::button :url="$url">
Log ind på din profil
</x-mail::button>

Hvis du ikke forventede denne mail, kan du roligt ignorere den. Dette link er personligt, så del det ikke med andre.

Venlig hilsen,
{{ config('app.name') }}
</x-mail::message>
