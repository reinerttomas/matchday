<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\Venue;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

/**
 * Read the events of a calendar response, each as its properties keyed by name and parameters, such as "DTSTART;TZID=Europe/Prague", with text values unescaped.
 *
 * @return Collection<int, array<string, string>>
 */
function calendarEvents(TestResponse $response): Collection
{
    // Lines longer than 75 octets are folded onto continuation lines that start with a space.
    $lines = explode("\r\n", str_replace("\r\n ", '', (string) $response->getContent()));
    $events = [];
    $event = null;

    foreach ($lines as $line) {
        if ($line === 'BEGIN:VEVENT') {
            $event = [];
        } elseif ($line === 'END:VEVENT') {
            $events[] = $event;
            $event = null;
        } elseif ($event !== null) {
            [$name, $value] = explode(':', $line, 2);
            $event[$name] = strtr($value, ['\\\\' => '\\', '\\,' => ',', '\\;' => ';', '\\n' => "\n"]);
        }
    }

    return collect($events);
}

/**
 * The team season of FBC Kutná Hora B in the current season 2026/27.
 */
function kutnaHoraCurrentTeamSeason(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/27']))
        ->create(['name' => 'FBC Kutná Hora B', 'competition_name' => '2. liga mužů, skupina 3']);
}

test('returns 404 for an unknown slug', function () {
    $this->get('/calendar/unknown-team.ics')->assertNotFound();
});

test('serves a fixture of the current team season as an event', function () {
    $teamSeason = kutnaHoraCurrentTeamSeason();
    Fixture::factory()->for($teamSeason)->home()->create([
        'external_id' => 1306757,
        'round' => 3,
        'opponent_name' => 'Tatran Střešovice C',
        'venue_id' => Venue::factory()->state(['name' => 'Sportovní hala Kutná Hora', 'address' => 'Čáslavská 274, Kutná Hora']),
        'date' => '2026-10-17',
        'time' => '15:00:00',
    ]);

    $response = $this->get('/calendar/kutna-hora-b.ics');

    $response->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect(calendarEvents($response)->sole())->toMatchArray([
        'SUMMARY' => 'FBC Kutná Hora B – Tatran Střešovice C',
        'LOCATION' => 'Sportovní hala Kutná Hora, Čáslavská 274, Kutná Hora',
        'DESCRIPTION' => "2. liga mužů, skupina 3\n3. kolo\nZápas na ceskyflorbal.cz: https://www.ceskyflorbal.cz/match/detail/default/1306757",
    ]);
});

test('keeps the uid of a revised fixture and carries its sequence', function () {
    $fixture = Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->create(['sequence' => 2]);

    $before = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();
    $fixture->update(['time' => '19:30:00', 'sequence' => 3]);
    $after = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($before['SEQUENCE'])->toBe('2')
        ->and($after['SEQUENCE'])->toBe('3')
        ->and($after['UID'])->toBe($before['UID']);
});

test('blocks 55 minutes from the start time in Prague time', function (string $date, string $time, string $startsAt, string $endsAt) {
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->create(['date' => $date, 'time' => $time]);

    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event)->toMatchArray([
        'DTSTART;TZID=Europe/Prague' => $startsAt,
        'DTEND;TZID=Europe/Prague' => $endsAt,
    ]);
})->with([
    'summer time' => ['2026-10-17', '15:00:00', '20261017T150000', '20261017T155500'],
    'winter time, ending after midnight' => ['2026-11-28', '23:30:00', '20261128T233000', '20261129T002500'],
]);

test('shows away fixtures with the opponent as the home side', function () {
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->away()->create(['opponent_name' => 'Tatran Střešovice C']);

    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event['SUMMARY'])->toBe('Tatran Střešovice C – FBC Kutná Hora B');
});

test('locates a fixture at a venue without an address by the venue name alone', function () {
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->create([
        'venue_id' => Venue::factory()->withoutAddress()->state(['name' => 'Sportovní hala Kutná Hora']),
    ]);

    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event['LOCATION'])->toBe('Sportovní hala Kutná Hora');
});

test('leaves out the location of a fixture without a venue', function () {
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->create(['venue_id' => null]);

    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event)->not->toHaveKey('LOCATION');
});

test('shows an address the administrator edited in the next response', function () {
    $venue = Venue::factory()->withoutAddress()->create(['name' => 'Sportovní hala Kutná Hora']);
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->for($venue)->create();
    $this->get('/calendar/kutna-hora-b.ics');

    $venue->update(['address' => 'Čáslavská 274, Kutná Hora']);
    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event['LOCATION'])->toBe('Sportovní hala Kutná Hora, Čáslavská 274, Kutná Hora');
});

test('leaves the round out of the description when it is unknown', function () {
    Fixture::factory()->for(kutnaHoraCurrentTeamSeason())->create(['external_id' => 1306757, 'round' => null]);

    $event = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->sole();

    expect($event['DESCRIPTION'])->toBe("2. liga mužů, skupina 3\nZápas na ceskyflorbal.cz: https://www.ceskyflorbal.cz/match/detail/default/1306757");
});

test('serves finished fixtures and leaves out fixtures of other seasons', function () {
    $team = Team::factory()->create(['slug' => 'kutna-hora-b']);
    $previousTeamSeason = TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2025/26']))->create();
    $currentTeamSeason = TeamSeason::factory()->for($team)->for(Season::factory()->current()->state(['name' => '2026/27']))->create();
    Fixture::factory()->for($previousTeamSeason)->create();
    $scheduled = Fixture::factory()->for($currentTeamSeason)->create();
    $finished = Fixture::factory()->for($currentTeamSeason)->finished()->create();

    $uids = calendarEvents($this->get('/calendar/kutna-hora-b.ics'))->pluck('UID');

    expect($uids->all())->toEqualCanonicalizing(["matchday-fixture-{$scheduled->id}", "matchday-fixture-{$finished->id}"]);
});

test('serves a valid calendar without events to a team outside the current season', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->state(['name' => '2025/26']))
        ->create();
    Season::factory()->current()->create();
    Fixture::factory()->for($teamSeason)->create();

    $response = $this->get('/calendar/kutna-hora-b.ics');

    $response->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect($response->getContent())->toStartWith("BEGIN:VCALENDAR\r\n")->toEndWith('END:VCALENDAR')
        ->and(calendarEvents($response))->toBeEmpty();
});
