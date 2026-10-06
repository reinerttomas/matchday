<x-mail::message>
# {{ __('imports.notifications.fixture_list_revised.heading') }}

{{ __('imports.notifications.fixture_list_revised.body', ['team_season' => $teamSeason]) }}

<x-mail::panel>
{!! $summaryLines !!}
</x-mail::panel>

<x-mail::button :url="$whatsAppUrl">
{{ __('imports.notifications.fixture_list_revised.button') }}
</x-mail::button>
</x-mail::message>
