<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Str;

final readonly class CalendarLinks
{
    /**
     * Link the team's permanent calendar address to the calendar apps players subscribe with, so every link keeps following the feed instead of importing a copy of it.
     *
     * @return array{address: string, google: string, webcal: string, outlook: string}
     */
    public function for(Team $team): array
    {
        $address = $this->address($team);
        $webcal = Str::replaceMatches('/^https?:\/\//', 'webcal://', $address);

        return [
            'address' => $address,
            // Google Calendar does not reliably accept an https address as cid, but subscribes to the same feed given as webcal.
            'google' => 'https://calendar.google.com/calendar/r?'.http_build_query(['cid' => $webcal], encoding_type: PHP_QUERY_RFC3986),
            'webcal' => $webcal,
            'outlook' => 'https://outlook.live.com/calendar/0/addfromweb?'.http_build_query(['url' => $address, 'name' => $team->calendarName()], encoding_type: PHP_QUERY_RFC3986),
        ];
    }

    /**
     * Get the team's permanent calendar address, which stays the same across seasons.
     */
    public function address(Team $team): string
    {
        return route('calendar', $team);
    }
}
