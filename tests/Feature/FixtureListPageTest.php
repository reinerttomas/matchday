<?php

declare(strict_types=1);

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    // Sunday 4 October 2026, noon in Prague.
    travelTo('2026-10-04 10:00:00');
});

/**
 * The team season the Rozpis zápasů page shows by default: FBC Kutná Hora B, the only team season in the current season, already imported.
 */
function importedTeamSeason(): TeamSeason
{
    $teamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for(Season::factory()->current())->create();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 06:00:00']);

    return $teamSeason;
}

/**
 * Get the fixtures the page lists, in their order.
 *
 * @return list<array<string, mixed>>
 */
function listedFixtures(string $url = '/fixtures'): array
{
    return test()->get($url)->inertiaProps('fixtureList.fixtures');
}

test('lists only the upcoming fixtures of the selected team season by default, counting both views', function () {
    $teamSeason = importedTeamSeason();
    Fixture::factory()->for($teamSeason)->finished()->create(['date' => '2026-10-03']);
    $today = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-04']);
    $later = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11']);
    Fixture::factory()->create(['date' => '2026-10-11']);
    // Shortly after midnight in Prague, while it is still 3 October in UTC.
    travelTo('2026-10-03 22:30:00');

    $response = $this->get('/fixtures');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('fixtures/index')
        ->where('fixtureList.period', 'upcoming')
        ->where('fixtureList.upcomingCount', 2)
        ->where('fixtureList.seasonCount', 3)
        ->etc());
    expect(array_column(listedFixtures(), 'id'))->toBe([$today->id, $later->id]);
});

test('lists the whole season in date and time order, with a TBD time last on its day', function () {
    $teamSeason = importedTeamSeason();
    $tbd = Fixture::factory()->for($teamSeason)->tbdTime()->create(['date' => '2026-10-11']);
    $evening = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11', 'time' => '19:00:00']);
    $past = Fixture::factory()->for($teamSeason)->finished()->create(['date' => '2026-09-20']);
    $morning = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11', 'time' => '09:00:00']);

    $response = $this->get('/fixtures?period=season');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.period', 'season')
        ->where('fixtureList.upcomingCount', 3)
        ->where('fixtureList.seasonCount', 4)
        ->etc());
    expect(array_column(listedFixtures('/fixtures?period=season'), 'id'))->toBe([$past->id, $morning->id, $evening->id, $tbd->id]);
});

test('lists fixtures of different months as one list, without month headings', function () {
    $teamSeason = importedTeamSeason();
    $september = Fixture::factory()->for($teamSeason)->finished()->create(['date' => '2026-09-27']);
    $october = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11']);
    $january = Fixture::factory()->for($teamSeason)->create(['date' => '2027-01-10']);

    $response = $this->get('/fixtures?period=season');

    $response->assertInertia(fn (Assert $page) => $page
        ->missing('fixtureList.months')
        ->etc());
    expect(array_column($response->inertiaProps('fixtureList.fixtures'), 'id'))->toBe([$september->id, $october->id, $january->id]);
});

test('links the team season\'s fixture list on ceskyflorbal.cz', function () {
    $teamSeason = importedTeamSeason();

    $response = $this->get('/fixtures');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.sourceUrl', $teamSeason->source_url)
        ->etc());
});

test('describes an ordinary scheduled home fixture without a badge', function () {
    $fixture = Fixture::factory()->for(importedTeamSeason())->home()
        ->for(Venue::factory()->state(['name' => 'Sportovní hala Kutná Hora']))
        ->create(['date' => '2026-10-04', 'time' => '09:00:00', 'opponent_name' => 'Las Plantas', 'round' => 4]);

    expect(listedFixtures()[0])->toBe([
        'id' => $fixture->id,
        'day' => 'NE 4. 10.',
        'round' => '4. kolo',
        'time' => '9:00',
        'isHome' => true,
        'homeTeam' => 'FBC Kutná Hora B',
        'awayTeam' => 'Las Plantas',
        'venue' => 'Sportovní hala Kutná Hora',
        'status' => 'scheduled',
        'badges' => [],
        'score' => null,
        'revisions' => [],
    ]);
});

test('names our team as the away side of an away fixture', function () {
    Fixture::factory()->for(importedTeamSeason())->away()->create(['opponent_name' => 'Las Plantas']);

    expect(listedFixtures()[0])->toMatchArray([
        'isHome' => false,
        'homeTeam' => 'Las Plantas',
        'awayTeam' => 'FBC Kutná Hora B',
    ]);
});

test('names the round as the federation does, or leaves it empty while unknown', function (?int $round, bool $isRescheduled, ?string $roundLabel) {
    Fixture::factory()->for(importedTeamSeason())
        ->when($isRescheduled, fn ($factory) => $factory->rescheduled())
        ->create(['round' => $round]);

    expect(listedFixtures()[0]['round'])->toBe($roundLabel);
})->with([
    'known round' => [12, false, '12. kolo'],
    'rescheduled fixture' => [4, true, '4. kolo'],
    'unknown round' => [null, false, null],
]);

test('leaves the time of a TBD fixture empty', function () {
    Fixture::factory()->for(importedTeamSeason())->tbdTime()->create();

    expect(listedFixtures()[0]['time'])->toBeNull();
});

test('marks a special fixture with its badge', function (string $state, array $badge) {
    Fixture::factory()->for(importedTeamSeason())->{$state}()->create();

    expect(listedFixtures()[0])->toMatchArray([
        'status' => $state === 'rescheduled' ? 'scheduled' : $state,
        'badges' => [$badge],
        'score' => null,
    ]);
})->with([
    'rescheduled' => ['rescheduled', ['kind' => 'rescheduled', 'label' => 'Dohrávka']],
    'postponed' => ['postponed', ['kind' => 'postponed', 'label' => 'Odloženo']],
    'cancelled' => ['cancelled', ['kind' => 'cancelled', 'label' => 'Zrušeno']],
]);

test('shows the result of a finished fixture from our team\'s point of view, with the score', function (string $side, int $homeScore, int $awayScore, array $badge, string $score) {
    Fixture::factory()->for(importedTeamSeason())->finished()->{$side}()->create([
        'home_score' => $homeScore,
        'away_score' => $awayScore,
    ]);

    expect(listedFixtures('/fixtures?period=season')[0])->toMatchArray([
        'status' => 'finished',
        'badges' => [$badge],
        'score' => $score,
    ]);
})->with([
    'win at home' => ['home', 5, 3, ['kind' => 'win', 'label' => 'Výhra'], '5:3'],
    'loss at home' => ['home', 2, 4, ['kind' => 'loss', 'label' => 'Prohra'], '2:4'],
    'draw at home' => ['home', 3, 3, ['kind' => 'draw', 'label' => 'Remíza'], '3:3'],
    'win away' => ['away', 3, 5, ['kind' => 'win', 'label' => 'Výhra'], '3:5'],
    'loss away' => ['away', 6, 1, ['kind' => 'loss', 'label' => 'Prohra'], '6:1'],
    'draw away' => ['away', 2, 2, ['kind' => 'draw', 'label' => 'Remíza'], '2:2'],
]);

test('marks a finished fixture without a score as played, without a result', function () {
    Fixture::factory()->for(importedTeamSeason())->finished()->create(['home_score' => null, 'away_score' => null]);

    expect(listedFixtures('/fixtures?period=season')[0])->toMatchArray([
        'status' => 'finished',
        'badges' => [['kind' => 'finished', 'label' => 'Odehráno']],
        'score' => null,
    ]);
});

test('marks a fixture revised in the last 7 days as changed', function (string $revisedAt, bool $isChanged) {
    $teamSeason = importedTeamSeason();
    $fixture = Fixture::factory()->for($teamSeason)->create();
    Revision::factory()->for($fixture)->for(Import::factory()->for($teamSeason))
        ->fieldChange(RevisionField::Time, null, '19:00')
        ->create(['created_at' => $revisedAt]);

    $badges = listedFixtures()[0]['badges'];

    expect($badges)->toBe($isChanged ? [['kind' => 'revised', 'label' => 'Změněno']] : []);
})->with([
    'just now' => ['2026-10-04 10:00:00', true],
    'exactly 7 days ago' => ['2026-09-27 10:00:00', true],
    'just over 7 days ago' => ['2026-09-27 09:59:59', false],
]);

test('lists a changed fixture\'s recent revisions as old → new values formatted for display', function () {
    $teamSeason = importedTeamSeason();
    $fixture = Fixture::factory()->for($teamSeason)->rescheduled()->create();
    $olderImport = Import::factory()->for($teamSeason)->create();
    $recentImport = Import::factory()->for($teamSeason)->create();
    Revision::factory()->for($fixture)->for($olderImport)
        ->fieldChange(RevisionField::Venue, 'Hala Kolín', 'Hala Čáslav')
        ->create(['created_at' => '2026-09-20 08:00:00']);
    $revisions = [
        [RevisionField::Date, '2026-10-04', '2026-10-11'],
        [RevisionField::Time, null, '19:00'],
        [RevisionField::Venue, null, 'Sportovní hala Kutná Hora'],
        [RevisionField::Status, 'postponed', 'scheduled'],
        [RevisionField::IsRescheduled, '0', '1'],
        [RevisionField::HomeScore, '3', null],
        [RevisionField::AwayScore, null, '4'],
    ];
    foreach ($revisions as $index => [$field, $oldValue, $newValue]) {
        Revision::factory()->for($fixture)->for($recentImport)
            ->fieldChange($field, $oldValue, $newValue)
            ->create(['created_at' => now()->subDay()->addSeconds($index)]);
    }

    $fixtureProps = listedFixtures()[0];

    expect($fixtureProps['revisions'])->toBe([
        'Datum: 4. 10. 2026 → 11. 10. 2026',
        'Čas: TBD → 19:00',
        'Hala: – → Sportovní hala Kutná Hora',
        'Stav: Odloženo → Naplánováno',
        'Dohrávka: ne → ano',
        'Skóre domácích: 3 → –',
        'Skóre hostů: – → 4',
    ]);
    expect($fixtureProps['badges'])->toBe([
        ['kind' => 'rescheduled', 'label' => 'Dohrávka'],
        ['kind' => 'revised', 'label' => 'Změněno'],
    ]);
});

test('lists a fixture that appeared after the initial import as a new fixture', function () {
    $teamSeason = importedTeamSeason();
    $fixture = Fixture::factory()->for($teamSeason)->create();
    Revision::factory()->for($fixture)->for(Import::factory()->for($teamSeason))->fixtureAdded()->create();

    expect(listedFixtures()[0]['revisions'])->toBe(['Nový zápas v rozpisu']);
});

test('loads revisions without a query per fixture', function () {
    $teamSeason = importedTeamSeason();
    $import = Import::factory()->for($teamSeason)->create();
    Revision::factory()->for($import)->create();
    DB::enableQueryLog();
    $this->get('/fixtures');
    $queriesForOneFixture = count(DB::getQueryLog());

    Revision::factory()->count(2)->for($import)->create();
    Revision::factory()->for(Import::factory()->for($teamSeason))->create();
    DB::flushQueryLog();
    $this->get('/fixtures');

    expect(count(DB::getQueryLog()))->toBe($queriesForOneFixture);
});

test('warns when the last import ended as error or aborted', function (string $state, string $reason) {
    $teamSeason = importedTeamSeason();
    Import::factory()->for($teamSeason)->{$state}($reason)->create(['started_at' => '2026-10-04 08:00:00']);

    $response = $this->get('/fixtures');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.lastImportFailure', [
            'status' => $state,
            'reason' => $reason,
        ])
        ->etc());
})->with([
    'error' => ['error', 'HTTP 403 – požadavek zablokován'],
    'aborted' => ['aborted', 'Parser vrátil 0 zápasů (minule 24)'],
]);

test('keeps warning about a failed import while the next import runs', function () {
    $teamSeason = importedTeamSeason();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-04 08:00:00']);
    Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-04 09:59:00']);

    $response = $this->get('/fixtures');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.lastImportFailure.status', 'error')
        ->etc());
});

test('does not warn once the last import ended ok', function () {
    $teamSeason = importedTeamSeason();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-04 08:00:00']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 09:00:00']);

    $response = $this->get('/fixtures');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.isImported', true)
        ->where('fixtureList.lastImportFailure', null)
        ->etc());
});

test('treats a team season without a successful import as never imported, still warning about its failed import', function () {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->notImported()->create();
    Import::factory()->for($teamSeason)->error()->create();

    $response = $this->get('/fixtures');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.isImported', false)
        ->where('fixtureList.lastImportFailure.status', 'error')
        ->where('fixtureList.upcomingCount', 0)
        ->where('fixtureList.seasonCount', 0)
        ->where('fixtureList.fixtures', [])
        ->etc());
});

test('reports whether an import of the team season is running', function (?string $importState, ?string $startedAt, bool $isRunning) {
    $teamSeason = importedTeamSeason();
    if ($importState !== null) {
        Import::factory()->for($teamSeason)->{$importState}()->create(['started_at' => $startedAt]);
    }
    Import::factory()->running()->create(['started_at' => '2026-10-04 09:58:00']);

    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('isImportRunning', $isRunning)
        ->etc());
})->with([
    'no running import' => [null, null, false],
    'a running import' => ['running', '2026-10-04 09:58:00', true],
    'a running import started just under 15 minutes ago' => ['running', '2026-10-04 09:45:01', true],
    'a running import older than 15 minutes, taken for dead' => ['running', '2026-10-04 09:44:59', false],
    'a finished manual import' => ['manual', '2026-10-04 09:58:00', false],
]);

test('tells how long ago the last successful import finished', function () {
    $teamSeason = importedTeamSeason();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 09:54:00', 'finished_at' => '2026-10-04 09:55:00']);
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-04 09:57:00', 'finished_at' => '2026-10-04 09:58:00']);
    Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-04 09:59:00']);

    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.lastImportFinished', 'Naposledy staženo před 5 minutami')
        ->etc());

    travelTo('2026-10-04 13:00:00');

    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.lastImportFinished', 'Naposledy staženo před 3 hodinami')
        ->etc());
});

test('tells nothing about the last import while the team season has never been imported', function () {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->notImported()->create();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-04 09:54:00', 'finished_at' => '2026-10-04 09:55:00']);
    Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-04 09:59:00']);

    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('fixtureList.isImported', false)
        ->where('fixtureList.lastImportFinished', null)
        ->where('isImportRunning', true)
        ->etc());
});

test('shows no fixture list while the selected season has no team seasons', function () {
    Season::factory()->current()->create();

    $response = $this->get('/fixtures');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('fixtures/index')
        ->where('fixtureList', null)
        ->where('isImportRunning', false)
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/fixtures')->assertRedirect('/login');
});
