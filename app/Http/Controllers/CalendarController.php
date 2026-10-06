<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\CalendarWriter;
use Illuminate\Http\Response;

final readonly class CalendarController
{
    /**
     * Serve the team's calendar feed, which players subscribe to in their calendar apps.
     */
    public function __invoke(Team $team, CalendarWriter $calendarWriter): Response
    {
        return response($calendarWriter->write($team), headers: [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }
}
