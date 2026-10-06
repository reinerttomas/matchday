<x-mail::message>
# {{ __("imports.notifications.failed.{$outcome}.heading") }}

{{ __("imports.notifications.failed.{$outcome}.body", ['team_season' => $teamSeason]) }}

{{ __('imports.notifications.failed.reason', ['reason' => $reason]) }}

<x-mail::button :url="$sourceUrl">
{{ __('imports.notifications.failed.button') }}
</x-mail::button>
</x-mail::message>
