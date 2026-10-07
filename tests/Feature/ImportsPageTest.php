<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
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
 * The team season the Importy page shows by default: the only one in the current season.
 */
function selectedTeamSeason(): TeamSeason
{
    return TeamSeason::factory()->for(Season::factory()->current())->create();
}

test('lists only the selected team season\'s imports', function () {
    $teamSeason = selectedTeamSeason();
    Import::factory()->for($teamSeason)->create();
    Import::factory()->create();

    $this->get('/imports')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('imports/index')
        ->has('importHistory.imports.data', 1)
        ->etc());
});

test('lists imports newest first', function () {
    $teamSeason = selectedTeamSeason();
    $older = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 08:00:00']);
    $newest = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 08:00:00']);
    $middle = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 12:00:00']);

    $imports = $this->get('/imports')->inertiaProps('importHistory.imports.data');

    expect(array_column($imports, 'id'))->toBe([$newest->id, $middle->id, $older->id]);
});

test('shows 20 imports per page', function () {
    $teamSeason = selectedTeamSeason();
    Import::factory()->count(21)->for($teamSeason)->sequence(fn ($sequence) => ['started_at' => now()->subHours($sequence->index + 1)])->create();
    $oldest = Import::query()->oldest('started_at')->firstOrFail();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->has('importHistory.imports.data', 20)
        ->where('importHistory.imports.total', 21)
        ->where('importHistory.imports.last_page', 2)
        ->etc());

    $this->get('/imports?page=2')->assertInertia(fn (Assert $page) => $page
        ->has('importHistory.imports.data', 1)
        ->where('importHistory.imports.data.0.id', $oldest->id)
        ->etc());
});

/**
 * Imports of the selected team season, one of each kind the tabs tell apart, from the oldest to the newest.
 *
 * @return array{plain: Import, revised: Import, failedWithRevision: Import, error: Import, aborted: Import}
 */
function importsOfEachKind(): array
{
    $teamSeason = selectedTeamSeason();
    $imports = [
        'plain' => Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 01:00:00']),
        'revised' => Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 02:00:00']),
        'failedWithRevision' => Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-06 03:00:00']),
        'error' => Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-06 04:00:00']),
        'aborted' => Import::factory()->for($teamSeason)->aborted()->create(['started_at' => '2026-10-06 05:00:00']),
    ];
    Revision::factory()->count(2)->for($imports['revised'])->create();
    Revision::factory()->for($imports['failedWithRevision'])->create();

    return $imports;
}

test('lists every import and counts each tab over the team season\'s history when no filter is chosen', function () {
    importsOfEachKind();
    Revision::factory()->for(Import::factory()->error())->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.filter', 'all')
        ->where('importHistory.allCount', 5)
        ->where('importHistory.revisedCount', 2)
        ->where('importHistory.failedCount', 3)
        ->has('importHistory.imports.data', 5)
        ->etc());
});

test('narrows the history to the imports with revisions or the failed ones', function (string $filter, array $kinds) {
    $imports = importsOfEachKind();

    $response = $this->get("/imports?filter={$filter}");

    $response->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.filter', $filter)
        ->where('importHistory.allCount', 5)
        ->where('importHistory.revisedCount', 2)
        ->where('importHistory.failedCount', 3)
        ->where('importHistory.imports.total', count($kinds))
        ->etc());
    expect(array_column($response->inertiaProps('importHistory.imports.data'), 'id'))
        ->toBe(array_map(fn (string $kind): int => $imports[$kind]->id, $kinds));
})->with([
    'with revisions' => ['revised', ['failedWithRevision', 'revised']],
    'failed' => ['failed', ['aborted', 'error', 'failedWithRevision']],
]);

test('falls back to every import for an unknown filter', function (string $query) {
    importsOfEachKind();

    $this->get("/imports?{$query}")->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.filter', 'all')
        ->has('importHistory.imports.data', 5)
        ->etc());
})->with([
    'unknown value' => ['filter=unknown'],
    'list of values' => ['filter[]=failed'],
]);

test('keeps the filter in the pagination links', function () {
    $teamSeason = selectedTeamSeason();
    Import::factory()->count(21)->for($teamSeason)->error()->sequence(fn ($sequence) => ['started_at' => now()->subHours($sequence->index + 1)])->create();
    Import::factory()->for($teamSeason)->create(['started_at' => now()->subDay()]);

    $firstPage = $this->get('/imports?filter=failed')->inertiaProps('importHistory.imports');
    $secondPage = $this->get('/imports?filter=failed&page=2')->inertiaProps('importHistory.imports');

    expect($firstPage['next_page_url'])->toEndWith('/imports?filter=failed&page=2')
        ->and($secondPage['prev_page_url'])->toEndWith('/imports?filter=failed&page=1')
        ->and($secondPage['data'])->toHaveCount(1)
        ->and($secondPage['data'][0]['status'])->toBe('error');
});

test('redirects a page past the end to the last page, keeping the filter', function (string $query, string $lastPage) {
    $teamSeason = selectedTeamSeason();
    Import::factory()->count(21)->for($teamSeason)->error()->create();
    Import::factory()->for($teamSeason)->create();

    $this->get('/imports?'.$query)->assertRedirect($lastPage);
})->with([
    'filtered' => ['filter=failed&page=9', '/imports?filter=failed&page=2'],
    'filter with no imports' => ['filter=revised&page=2', '/imports?filter=revised&page=1'],
]);

test('lists no imports when none matches the filter', function () {
    Import::factory()->for(selectedTeamSeason())->create();

    $this->get('/imports?filter=failed')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.filter', 'failed')
        ->where('importHistory.allCount', 1)
        ->where('importHistory.failedCount', 0)
        ->has('importHistory.imports.data', 0)
        ->etc());
});

test('describes a successful scheduled import with its start date and time in Prague, duration, fixtures found and revisions', function () {
    $import = Import::factory()->for(selectedTeamSeason())->create([
        'started_at' => '2026-10-05 22:30:00',
        'finished_at' => '2026-10-05 22:31:05',
        'fixtures_found' => 24,
    ]);
    Revision::factory()->count(3)->for($import)->create();

    $item = $this->get('/imports')->inertiaProps('importHistory.imports.data.0');

    expect($item)->toBe([
        'id' => $import->id,
        'startedOn' => '6. 10. 2026',
        'startedAt' => '00:30',
        'trigger' => 'schedule',
        'status' => 'ok',
        'statusLabel' => 'OK',
        'reason' => null,
        'duration' => '1 min 5 s',
        'fixturesFoundLabel' => '24 zápasů',
        'revisionsCount' => 3,
        'revisionsLabel' => '3 změny',
    ]);
});

test('marks a manual import by its trigger', function () {
    Import::factory()->for(selectedTeamSeason())->manual()->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.imports.data.0.trigger', 'manual')
        ->etc());
});

test('names the fixtures found in the right plural form', function (int $fixturesFound, string $label) {
    Import::factory()->for(selectedTeamSeason())->create(['fixtures_found' => $fixturesFound]);

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.imports.data.0.fixturesFoundLabel', $label)
        ->etc());
})->with([
    'one' => [1, '1 zápas'],
    'two to four' => [4, '4 zápasy'],
    'five and more' => [22, '22 zápasů'],
    'none' => [0, '0 zápasů'],
]);

test('names the revisions in the right plural form', function (int $revisionsCount, string $label) {
    $import = Import::factory()->for(selectedTeamSeason())->create();
    Revision::factory()->count($revisionsCount)->for($import)->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.imports.data.0.revisionsCount', $revisionsCount)
        ->where('importHistory.imports.data.0.revisionsLabel', $label)
        ->etc());
})->with([
    'one' => [1, '1 změna'],
    'two to four' => [2, '2 změny'],
    'five and more' => [6, '6 změn'],
    'none' => [0, '0 změn'],
]);

test('gives the reason of a failed or aborted import', function (string $state, string $reason, string $statusLabel) {
    Import::factory()->for(selectedTeamSeason())->{$state}($reason)->create([
        'started_at' => '2026-10-06 06:00:00',
        'finished_at' => '2026-10-06 06:00:12',
    ]);

    $item = $this->get('/imports')->inertiaProps('importHistory.imports.data.0');

    expect($item)->toMatchArray([
        'status' => $state,
        'statusLabel' => $statusLabel,
        'reason' => $reason,
        'duration' => '12 s',
        'revisionsCount' => 0,
        'revisionsLabel' => '0 změn',
    ]);
})->with([
    'error' => ['error', 'HTTP 403 – požadavek zablokován', 'Chyba'],
    'aborted' => ['aborted', 'Parser vrátil 0 zápasů (minule 24)', 'Přerušeno'],
]);

test('shows a running import without a duration', function () {
    Import::factory()->for(selectedTeamSeason())->running()->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('importHistory.imports.data.0.status', 'running')
        ->where('importHistory.imports.data.0.statusLabel', 'Probíhá')
        ->where('importHistory.imports.data.0.duration', null)
        ->where('importHistory.imports.data.0.fixturesFoundLabel', null)
        ->etc());
});

test('counts revisions without a query per import', function () {
    $teamSeason = selectedTeamSeason();
    Revision::factory()->for(Import::factory()->for($teamSeason))->create();
    DB::enableQueryLog();
    $this->get('/imports');
    $queriesForOneImport = count(DB::getQueryLog());

    Revision::factory()->count(2)->for(Import::factory()->for($teamSeason))->create();
    Revision::factory()->for(Import::factory()->for($teamSeason))->create();
    DB::flushQueryLog();
    $this->get('/imports');

    expect(count(DB::getQueryLog()))->toBe($queriesForOneImport);
});

test('reports whether an import of the team season is running', function (bool $hasRunningImport) {
    $teamSeason = selectedTeamSeason();
    Import::factory()->for($teamSeason)->create();
    if ($hasRunningImport) {
        Import::factory()->for($teamSeason)->running()->create(['started_at' => now()->subMinute()]);
    }

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('isImportRunning', $hasRunningImport)
        ->etc());
})->with([
    'running' => [true],
    'not running' => [false],
]);

test('shows no imports while the selected season has no team seasons', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);

    $this->get('/imports')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('importHistory', null)
        ->where('isImportRunning', false)
        ->where('adminSelection.season.name', '2026/2027')
        ->where('adminSelection.teamSeason', null)
        ->where('adminSelection.teamSeasons', [])
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/imports')->assertRedirect('/login');
});
