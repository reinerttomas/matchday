<?php

declare(strict_types=1);

use App\Models\CalendarFetch;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;

const GOOGLE_CALENDAR_FETCHER = 'Google-Calendar-Importer';

const APPLE_CALENDAR_FETCHER = 'iOS/18.0 (22A3354) dataaccessd/1.0';

test('counts a fetch of the feed per team, day in Prague and user agent', function () {
    $team = Team::factory()->create(['slug' => 'kutna-hora-b']);
    // 23:30 UTC is already the next day in Prague.
    $this->travelTo('2026-10-16 23:30:00');

    $this->withHeader('User-Agent', GOOGLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics')->assertOk();

    expect(CalendarFetch::query()->sole())
        ->team->is($team)->toBeTrue()
        ->date->toDateString()->toBe('2026-10-17')
        ->user_agent->toBe(GOOGLE_CALENDAR_FETCHER)
        ->user_agent_hash->toBe(hash('sha256', GOOGLE_CALENDAR_FETCHER))
        ->count->toBe(1);
});

test('increments the count of a second fetch from the same user agent on the same day', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);
    $this->travelTo('2026-10-17 06:00:00');
    $this->withHeader('User-Agent', GOOGLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics');
    $this->travelTo('2026-10-17 18:00:00');

    $this->withHeader('User-Agent', GOOGLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics')->assertOk();

    expect(CalendarFetch::query()->sole()->count)->toBe(2);
});

test('counts a fetch on the next day or from another user agent in a new row', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);
    $this->travelTo('2026-10-17 06:00:00');
    $this->withHeader('User-Agent', GOOGLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics');

    $this->withHeader('User-Agent', APPLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics');
    $this->travelTo('2026-10-18 06:00:00');
    $this->withHeader('User-Agent', GOOGLE_CALENDAR_FETCHER)->get('/calendar/kutna-hora-b.ics');

    expect(CalendarFetch::query()->orderBy('id')->get(['date', 'user_agent', 'count'])->map(fn (CalendarFetch $fetch): array => [
        $fetch->date->toDateString(), $fetch->user_agent, $fetch->count,
    ])->all())->toBe([
        ['2026-10-17', GOOGLE_CALENDAR_FETCHER, 1],
        ['2026-10-17', APPLE_CALENDAR_FETCHER, 1],
        ['2026-10-18', GOOGLE_CALENDAR_FETCHER, 1],
    ]);
});

test('cuts the user agent to 512 characters and hashes the cut one', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);
    $cutUserAgent = str_repeat('a', 500).str_repeat('b', 12);

    $this->withHeader('User-Agent', $cutUserAgent.str_repeat('b', 88))->get('/calendar/kutna-hora-b.ics');

    expect(CalendarFetch::query()->sole())
        ->user_agent->toBe($cutUserAgent)
        ->user_agent_hash->toBe(hash('sha256', $cutUserAgent));
});

test('still serves the feed and reports the error when the fetch cannot be counted', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);
    Schema::drop('calendar_fetches');
    Exceptions::fake();

    $response = $this->get('/calendar/kutna-hora-b.ics');

    $response->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    Exceptions::assertReported(QueryException::class);
});
