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
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    travelTo('2026-10-06 12:00:00');
});

/**
 * The team season the Změny page shows by default: FBC Kutná Hora B, the only team season in the current season, with its initial import of 24 fixtures on 1 September, before any import the factory makes.
 */
function initiallyImportedTeamSeason(): TeamSeason
{
    $teamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for(Season::factory()->current()->state(['name' => '2026/2027']))->create();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-01 06:00:00', 'fixtures_found' => 24]);

    return $teamSeason;
}

/**
 * Write a revision's parts the way the page shows them, with the struck-through old value between tildes: "Čas: ~~TBD~~ → 9:00".
 *
 * @param  list<array{text: string, kind: string}>  $parts
 */
function revisionText(array $parts): string
{
    return implode('', array_map(fn (array $part): string => $part['kind'] === 'old_value' ? "~~{$part['text']}~~" : $part['text'], $parts));
}

test('lists the imports that recorded revisions newest first, without the initial import and imports without revisions', function () {
    $teamSeason = initiallyImportedTeamSeason();
    $older = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-02 06:00:00']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 06:00:00']);
    $newest = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    Revision::factory()->for($older)->create();
    Revision::factory()->for($newest)->create();
    Revision::factory()->for($teamSeason->initialImport)->create();
    Revision::factory()->for(Import::factory()->for(TeamSeason::factory()->for(Season::factory()->state(['name' => '2025/2026']))))->create();

    $response = $this->get('/changes');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('changes/index')->etc());
    expect(array_column($response->inertiaProps('revisionHistory.imports'), 'id'))->toBe([$newest->id, $older->id]);
});

test('describes an import by its start time in Prague and each revised fixture by its day and sides', function () {
    $teamSeason = initiallyImportedTeamSeason();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    $fixture = Fixture::factory()->for($teamSeason)->home()->create(['date' => '2026-10-11', 'time' => '09:00:00', 'opponent_name' => 'Las Plantas']);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::Time, null, '09:00')->create();

    $imports = $this->get('/changes')->inertiaProps('revisionHistory.imports');

    expect($imports)->toBe([[
        'id' => $import->id,
        'startedAt' => '5. 10. 2026 08:00',
        'notified' => null,
        'fixtures' => [[
            'id' => $fixture->id,
            'day' => 'NE 11. 10.',
            'matchup' => [
                ['text' => 'FBC Kutná Hora B', 'isOurTeam' => true],
                ['text' => ' – ', 'isOurTeam' => false],
                ['text' => 'Las Plantas', 'isOurTeam' => false],
            ],
            'revisions' => [[
                ['text' => 'Čas', 'kind' => 'field'],
                ['text' => ': ', 'kind' => 'text'],
                ['text' => 'TBD', 'kind' => 'old_value'],
                ['text' => ' → ', 'kind' => 'text'],
                ['text' => '9:00', 'kind' => 'new_value'],
            ]],
        ]],
    ]]);
});

test('shows each revision as its field\'s old → new value formatted for display', function () {
    $teamSeason = initiallyImportedTeamSeason();
    $import = Import::factory()->for($teamSeason)->create();
    $fixture = Fixture::factory()->for($teamSeason)->rescheduled()->create(['date' => '2026-10-11']);
    $revisions = [
        [RevisionField::Date, '2026-10-04', '2026-10-11'],
        [RevisionField::Time, '19:00', null],
        [RevisionField::Venue, 'Hala Kolín', 'Sportovní hala Kutná Hora'],
        [RevisionField::Status, 'postponed', 'scheduled'],
        [RevisionField::IsRescheduled, '0', '1'],
        [RevisionField::HomeScore, null, '5'],
    ];
    foreach ($revisions as [$field, $oldValue, $newValue]) {
        Revision::factory()->for($import)->for($fixture)->fieldChange($field, $oldValue, $newValue)->create();
    }

    $fixtureRevisions = $this->get('/changes')->inertiaProps('revisionHistory.imports.0.fixtures.0.revisions');

    expect(array_map(revisionText(...), $fixtureRevisions))->toBe([
        'Datum: ~~4. 10. 2026~~ → 11. 10. 2026',
        'Čas: ~~19:00~~ → TBD',
        'Hala: ~~Hala Kolín~~ → Sportovní hala Kutná Hora',
        'Stav: ~~Odloženo~~ → Naplánováno',
        'Dohrávka: ~~ne~~ → ano',
        'Skóre domácích: ~~–~~ → 5',
    ]);
});

test('lists an import\'s fixtures in date order as they were right after the import, even after a later import moved one', function () {
    $teamSeason = initiallyImportedTeamSeason();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-02 06:00:00']);
    $laterImport = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    $moved = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-25']);
    $timeSet = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-21']);
    Revision::factory()->for($import)->for($timeSet)->create();
    Revision::factory()->for($import)->for($moved)->fieldChange(RevisionField::Date, '2026-10-11', '2026-10-18')->create();
    Revision::factory()->for($laterImport)->for($moved)->fieldChange(RevisionField::Date, '2026-10-18', '2026-10-25')->create();

    $fixtures = $this->get('/changes')->inertiaProps('revisionHistory.imports.1.fixtures');

    expect(array_map(fn (array $fixture): array => [$fixture['id'], $fixture['day']], $fixtures))->toBe([
        [$moved->id, 'NE 18. 10.'],
        [$timeSet->id, 'ST 21. 10.'],
    ]);
});

test('lists a fixture that appeared after the initial import as a new fixture', function () {
    $teamSeason = initiallyImportedTeamSeason();
    Revision::factory()->for(Import::factory()->for($teamSeason))->fixtureAdded()->create();

    $fixtureRevisions = $this->get('/changes')->inertiaProps('revisionHistory.imports.0.fixtures.0.revisions');

    expect($fixtureRevisions)->toBe([[['text' => 'Nový zápas v rozpisu', 'kind' => 'added']]]);
});

test('shows the initial import as the number of fixtures it added and those fixtures, without the ones added later', function () {
    $teamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for(Season::factory()->current())->create();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-09-30 06:00:00']);
    $initialImport = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-01 06:00:00', 'fixtures_found' => 24]);
    $later = Fixture::factory()->for($teamSeason)->away()->create(['date' => '2026-10-18', 'opponent_name' => 'Las Plantas']);
    $earlier = Fixture::factory()->for($teamSeason)->create(['date' => '2026-10-11']);
    Revision::factory()->for(Import::factory()->for($teamSeason)->state(['started_at' => '2026-10-03 06:00:00']))->fixtureAdded()->create();

    $initialImportProps = $this->get('/changes')->inertiaProps('revisionHistory.initialImport');

    expect($initialImportProps)->toMatchArray([
        'id' => $initialImport->id,
        'startedAt' => '1. 10. 2026 08:00',
        'summary' => '24 zápasů přidáno do rozpisu',
    ]);
    expect($initialImportProps['fixtures'])->toHaveCount(2)
        ->and($initialImportProps['fixtures'][0]['id'])->toBe($earlier->id)
        ->and($initialImportProps['fixtures'][1])->toBe([
            'id' => $later->id,
            'day' => 'NE 18. 10.',
            'matchup' => [
                ['text' => 'Las Plantas', 'isOurTeam' => false],
                ['text' => ' – ', 'isOurTeam' => false],
                ['text' => 'FBC Kutná Hora B', 'isOurTeam' => true],
            ],
        ]);
});

test('counts the fixtures the initial import added in Czech plural forms', function (int $fixturesFound, string $summary) {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->create();
    Import::factory()->for($teamSeason)->create(['fixtures_found' => $fixturesFound]);

    $this->get('/changes')->assertInertia(fn (Assert $page) => $page
        ->where('revisionHistory.initialImport.summary', $summary)
        ->etc());
})->with([
    [1, '1 zápas přidán do rozpisu'],
    [3, '3 zápasy přidány do rozpisu'],
    [24, '24 zápasů přidáno do rozpisu'],
]);

test('tells when the team was sent an import\'s change summary', function () {
    $teamSeason = initiallyImportedTeamSeason();
    $sent = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00', 'notified_at' => '2026-10-05 06:15:00']);
    $unsent = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 06:00:00']);
    Revision::factory()->for($sent)->create();
    Revision::factory()->for($unsent)->create();

    $imports = $this->get('/changes')->inertiaProps('revisionHistory.imports');

    expect(array_column($imports, 'notified', 'id'))->toBe([
        $sent->id => 'Odesláno týmu 5. 10. 2026 v 08:15',
        $unsent->id => null,
    ]);
});

test('shares how many of the selected team season\'s change summaries wait to be sent', function () {
    $teamSeason = initiallyImportedTeamSeason();
    Revision::factory()->for($teamSeason->initialImport)->create();
    Revision::factory()->for(Import::factory()->for($teamSeason))->create();
    Revision::factory()->count(2)->for(Import::factory()->for($teamSeason))->create();
    Revision::factory()->for(Import::factory()->for($teamSeason)->notified())->create();
    Import::factory()->for($teamSeason)->create();
    $otherTeamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($teamSeason->season)->create();
    Revision::factory()->for(Import::factory()->for($otherTeamSeason))->create();

    $this->get('/changes')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.teamSeason.id', $teamSeason->id)
        ->where('unsentChangeSummaryCount', 2)
        ->etc());
    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('unsentChangeSummaryCount', 2)
        ->etc());
});

test('shares no unsent change summaries when every import was sent or the season has no team seasons', function (bool $hasTeamSeason) {
    if ($hasTeamSeason) {
        Revision::factory()->for(Import::factory()->for(initiallyImportedTeamSeason())->notified())->create();
    } else {
        Season::factory()->current()->create();
    }

    $this->get('/changes')->assertInertia(fn (Assert $page) => $page
        ->where('unsentChangeSummaryCount', 0)
        ->etc());
})->with([
    'all sent' => [true],
    'no team seasons' => [false],
]);

test('lists no imports when the fixture list has not changed since the initial import', function () {
    initiallyImportedTeamSeason();

    $this->get('/changes')->assertInertia(fn (Assert $page) => $page
        ->where('revisionHistory.imports', [])
        ->where('revisionHistory.initialImport.summary', '24 zápasů přidáno do rozpisu')
        ->etc());
});

test('shows no initial import while the team season has never been imported', function () {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->notImported()->create();
    Import::factory()->for($teamSeason)->error()->create();

    $this->get('/changes')->assertInertia(fn (Assert $page) => $page
        ->where('revisionHistory', ['imports' => [], 'initialImport' => null])
        ->etc());
});

test('loads revisions without a query per import or fixture', function () {
    $teamSeason = initiallyImportedTeamSeason();
    Revision::factory()->for(Import::factory()->for($teamSeason))->create();
    DB::enableQueryLog();
    $this->get('/changes');
    $queriesForOneRevision = count(DB::getQueryLog());

    Revision::factory()->count(2)->for(Import::factory()->for($teamSeason))->create();
    Revision::factory()->for(Import::factory()->for($teamSeason))->fixtureAdded()->create();
    Fixture::factory()->count(2)->for($teamSeason)->create();
    DB::flushQueryLog();
    $this->get('/changes');

    expect(count(DB::getQueryLog()))->toBe($queriesForOneRevision);
});

test('shows no revision history while the selected season has no team seasons', function () {
    Season::factory()->current()->create();

    $this->get('/changes')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('changes/index')
        ->where('revisionHistory', null)
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/changes')->assertRedirect('/login');
});
