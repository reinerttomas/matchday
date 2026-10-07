<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\travelTo;

/**
 * The team season of FBC Kutná Hora B in the current season 2026/27, shown on the page at /t/kutna-hora-b.
 */
function kutnaHoraTeamSeason(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/27']))
        ->create([
            'external_id' => 45019,
            'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
            'name' => 'FBC Kutná Hora B',
            'competition_name' => '2. liga mužů, skupina 3',
        ]);
}

beforeEach(function () {
    // Sunday 4 October 2026, noon in Prague.
    travelTo('2026-10-04 10:00:00');
});

test('returns 404 for an unknown slug', function () {
    $this->get('/t/unknown-team')->assertNotFound();
});

test('shows the team season of the current season to a guest', function () {
    $teamSeason = kutnaHoraTeamSeason();
    TeamSeason::factory()->for($teamSeason->team)->for(Season::factory()->state(['name' => '2025/26']))->create(['name' => 'FBC Kutná Hora C']);

    $response = $this->get('/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('public/team')
        ->where('season', '2026/27')
        ->where('teamSeason.name', 'FBC Kutná Hora B')
        ->where('teamSeason.competition', '2. liga mužů, skupina 3')
        ->etc());
});

test('offers subscribe links derived from the team\'s permanent calendar address', function () {
    kutnaHoraTeamSeason();

    $response = $this->get('https://matchday.cz/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('pageUrl', 'https://matchday.cz/t/kutna-hora-b')
        ->where('calendar', [
            'address' => 'https://matchday.cz/calendar/kutna-hora-b.ics',
            'google' => 'https://calendar.google.com/calendar/r?cid=webcal%3A%2F%2Fmatchday.cz%2Fcalendar%2Fkutna-hora-b.ics',
            'webcal' => 'webcal://matchday.cz/calendar/kutna-hora-b.ics',
            'outlook' => 'https://outlook.live.com/calendar/0/addfromweb?url=https%3A%2F%2Fmatchday.cz%2Fcalendar%2Fkutna-hora-b.ics&name=FBC%20Kutn%C3%A1%20Hora%20B',
        ])
        ->etc());
});

test('lists fixtures from today in Prague on, in date and time order', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $later = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11', 'time' => '10:00:00']);
    $evening = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '19:00:00']);
    $morning = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '09:00:00']);
    Fixture::factory()->for($teamSeason)->finished()->create(['date' => '2026-10-03']);
    // Shortly after midnight in Prague, while it is still 3 October in UTC.
    travelTo('2026-10-03 22:30:00');

    $matchDays = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays');

    expect(collect($matchDays)->flatMap(fn (array $matchDay): array => array_column($matchDay['fixtures'], 'id'))->all())
        ->toBe([$morning->id, $evening->id, $later->id]);
});

test('lists fixtures with a tbd time after the timed fixtures of their match day', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $tbd = Fixture::factory()->for($teamSeason)->tbdTime()->create(['date' => '2026-10-04']);
    $timed = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '19:00:00']);

    $fixtures = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures');

    expect(array_column($fixtures, 'id'))->toBe([$timed->id, $tbd->id]);
});

test('groups fixtures by match day under the date, adding the year outside the current year', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '10:00:00']);
    Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '14:00:00']);
    Fixture::factory()->for($teamSeason)->create(['date' => '2027-01-09']);

    $matchDays = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays');

    expect($matchDays)->toHaveCount(2)
        ->and($matchDays[0])->toMatchArray(['date' => '2026-10-04', 'heading' => 'Neděle 4. října'])
        ->and($matchDays[0]['fixtures'])->toHaveCount(2)
        ->and($matchDays[1])->toMatchArray(['date' => '2027-01-09', 'heading' => 'Sobota 9. ledna 2027'])
        ->and($matchDays[1]['fixtures'])->toHaveCount(1);
});

test('shows the venue once in the match day heading when all its fixtures share it', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $venue = Venue::factory()->create(['name' => 'Sportovní hala Kutná Hora']);
    Fixture::factory()->count(2)->for($teamSeason)->for($venue)->create(['date' => '2026-10-04']);

    $matchDay = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0');

    expect($matchDay['venue'])->toBe('Sportovní hala Kutná Hora')
        ->and(array_column($matchDay['fixtures'], 'venue'))->toBe([null, null]);
});

test('shows the venue on each fixture when a match day has several venues', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '10:00:00', 'venue_id' => Venue::factory()->state(['name' => 'Sportovní hala Kutná Hora'])]);
    Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04', 'time' => '14:00:00', 'venue_id' => null]);

    $matchDay = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0');

    expect($matchDay['venue'])->toBeNull()
        ->and(array_column($matchDay['fixtures'], 'venue'))->toBe(['Sportovní hala Kutná Hora', null]);
});

test('shows a fixture with its start time, round and our team marked in home – away order', function (bool $isHome, array $matchup) {
    Fixture::factory()->for(kutnaHoraTeamSeason())->create([
        'is_home' => $isHome,
        'opponent_name' => 'Tatran Střešovice C',
        'round' => 3,
        'date' => '2026-10-04',
        'time' => '09:00:00',
    ]);

    $fixture = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures.0');

    expect($fixture)->toMatchArray([
        'time' => '9:00',
        'matchup' => $matchup,
        'round' => '3. kolo',
        'status' => 'scheduled',
        'statusLabel' => null,
        'score' => null,
    ]);
})->with([
    'home' => [true, [
        ['text' => 'FBC Kutná Hora B', 'isOurTeam' => true],
        ['text' => ' – ', 'isOurTeam' => false],
        ['text' => 'Tatran Střešovice C', 'isOurTeam' => false],
    ]],
    'away' => [false, [
        ['text' => 'Tatran Střešovice C', 'isOurTeam' => false],
        ['text' => ' – ', 'isOurTeam' => false],
        ['text' => 'FBC Kutná Hora B', 'isOurTeam' => true],
    ]],
]);

test('shows a fixture with a tbd time without a start time', function () {
    Fixture::factory()->for(kutnaHoraTeamSeason())->tbdTime()->create(['date' => '2026-10-04']);

    $fixture = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures.0');

    expect($fixture['time'])->toBeNull();
});

test('labels a rescheduled fixture as a dohrávka of its round', function () {
    Fixture::factory()->for(kutnaHoraTeamSeason())->rescheduled()->create(['round' => 3, 'date' => '2026-10-04']);

    $fixture = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures.0');

    expect($fixture['round'])->toBe('dohrávka 3. kola');
});

test('keeps postponed and cancelled fixtures in the list with a label', function (string $state, string $status, string $statusLabel) {
    Fixture::factory()->for(kutnaHoraTeamSeason())->{$state}()->create(['date' => '2026-10-04']);

    $fixture = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures.0');

    expect($fixture)->toMatchArray(['status' => $status, 'statusLabel' => $statusLabel, 'score' => null]);
})->with([
    'postponed' => ['postponed', 'postponed', 'Odloženo'],
    'cancelled' => ['cancelled', 'cancelled', 'Zrušeno'],
]);

test('shows the score of a finished fixture in home – away order', function () {
    Fixture::factory()->for(kutnaHoraTeamSeason())->away()->finished()->create([
        'date' => '2026-10-04',
        'home_score' => 7,
        'away_score' => 4,
    ]);

    $fixture = $this->get('/t/kutna-hora-b')->inertiaProps('teamSeason.matchDays.0.fixtures.0');

    expect($fixture)->toMatchArray(['status' => 'finished', 'statusLabel' => null, 'score' => '7:4']);
});

test('names the data source and the time of the last successful import in Prague time', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-03 14:04:30', 'finished_at' => '2026-10-03 14:05:00']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-02 08:00:00', 'finished_at' => '2026-10-02 08:00:20']);
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-04 06:00:00', 'finished_at' => '2026-10-04 06:00:10']);

    $response = $this->get('/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('teamSeason.sourceUrl', 'https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->where('teamSeason.lastImportedAt', '3. 10. 2026 v 16:05')
        ->etc());
});

test('has no last import time before the first successful import', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Import::factory()->for($teamSeason)->error()->create();
    Import::factory()->for($teamSeason)->running()->create();

    $response = $this->get('/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page->where('teamSeason.lastImportedAt', null)->etc());
});

test('has no match days once every fixture of the team season is in the past', function () {
    Fixture::factory()->for(kutnaHoraTeamSeason())->finished()->create(['date' => '2026-10-03']);

    $response = $this->get('/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('teamSeason.name', 'FBC Kutná Hora B')
        ->where('teamSeason.matchDays', [])
        ->etc());
});

test('names the current season when the team has no team season in it', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->state(['name' => '2025/26']))
        ->has(Fixture::factory()->state(['date' => '2026-10-11']))
        ->create();
    Season::factory()->current()->create(['name' => '2026/27']);

    $response = $this->get('/t/kutna-hora-b');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('public/team')
        ->where('season', '2026/27')
        ->where('teamSeason', null)
        ->where('calendar.outlook', fn (string $outlook): bool => str_ends_with($outlook, '&name=kutna-hora-b'))
        ->etc());
});
