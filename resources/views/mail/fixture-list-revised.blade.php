<x-mail::message>
# Rozpis zápasů se změnil

Import rozpisu zápasů týmu **{{ $teamSeason }}** z ceskyflorbal.cz našel změny. Tady je souhrn pro WhatsApp skupinu týmu:

<x-mail::panel>
{!! $summaryLines !!}
</x-mail::panel>

<x-mail::button :url="$whatsAppUrl">
Otevřít WhatsApp
</x-mail::button>
</x-mail::message>
