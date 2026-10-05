<x-mail::message>
# {{ $isAborted ? 'Import rozpisu byl přerušen' : 'Import rozpisu selhal' }}

Import rozpisu zápasů týmu **{{ $teamSeason }}** z ceskyflorbal.cz {{ $isAborted ? 'byl přerušen' : 'skončil chybou' }}. Uložený rozpis zápasů zůstal beze změny.

**Důvod:** {{ $reason }}

<x-mail::button :url="$sourceUrl">
Otevřít rozpis na ceskyflorbal.cz
</x-mail::button>
</x-mail::message>
