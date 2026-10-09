<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalendarFetches\RecordCalendarFetch;
use App\Models\Team;
use App\Services\CalendarWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

final readonly class CalendarController
{
    /**
     * Serve the team's calendar feed, which players subscribe to in their calendar apps, and count the fetch.
     */
    public function __invoke(Request $request, Team $team, CalendarWriter $calendarWriter, RecordCalendarFetch $recordCalendarFetch): Response
    {
        // Counting fetches is only statistics, so a failed count must never break players' calendars.
        try {
            $recordCalendarFetch->handle($team, (string) $request->userAgent());
        } catch (Throwable $exception) {
            report($exception);
        }

        return response($calendarWriter->write($team), headers: [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }
}
