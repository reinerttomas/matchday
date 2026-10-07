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
        ->has('imports.data', 1)
        ->etc());
});

test('lists imports newest first', function () {
    $teamSeason = selectedTeamSeason();
    $older = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 08:00:00']);
    $newest = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 08:00:00']);
    $middle = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 12:00:00']);

    $imports = $this->get('/imports')->inertiaProps('imports.data');

    expect(array_column($imports, 'id'))->toBe([$newest->id, $middle->id, $older->id]);
});

test('shows 20 imports per page', function () {
    $teamSeason = selectedTeamSeason();
    Import::factory()->count(21)->for($teamSeason)->sequence(fn ($sequence) => ['started_at' => now()->subHours($sequence->index + 1)])->create();
    $oldest = Import::query()->oldest('started_at')->firstOrFail();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->has('imports.data', 20)
        ->where('imports.total', 21)
        ->where('imports.last_page', 2)
        ->etc());

    $this->get('/imports?page=2')->assertInertia(fn (Assert $page) => $page
        ->has('imports.data', 1)
        ->where('imports.data.0.id', $oldest->id)
        ->etc());
});

test('describes a successful scheduled import with its start time in Prague, duration, fixtures found and revision count', function () {
    $import = Import::factory()->for(selectedTeamSeason())->create([
        'started_at' => '2026-10-06 06:00:00',
        'finished_at' => '2026-10-06 06:01:05',
        'fixtures_found' => 24,
    ]);
    Revision::factory()->count(3)->for($import)->create();

    $item = $this->get('/imports')->inertiaProps('imports.data.0');

    expect($item)->toBe([
        'id' => $import->id,
        'startedAt' => '6. 10. 2026 08:00',
        'trigger' => 'schedule',
        'triggerLabel' => 'automaticky',
        'status' => 'ok',
        'statusLabel' => 'OK',
        'reason' => null,
        'duration' => '1 min 5 s',
        'fixturesFound' => 24,
        'revisionsCount' => 3,
    ]);
});

test('marks a manual import as "ručně"', function () {
    Import::factory()->for(selectedTeamSeason())->manual()->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('imports.data.0.trigger', 'manual')
        ->where('imports.data.0.triggerLabel', 'ručně')
        ->etc());
});

test('shows the reason under a failed or aborted import', function (string $state, string $reason, string $statusLabel) {
    Import::factory()->for(selectedTeamSeason())->{$state}($reason)->create([
        'started_at' => '2026-10-06 06:00:00',
        'finished_at' => '2026-10-06 06:00:12',
    ]);

    $item = $this->get('/imports')->inertiaProps('imports.data.0');

    expect($item)->toMatchArray([
        'status' => $state,
        'statusLabel' => $statusLabel,
        'reason' => $reason,
        'duration' => '12 s',
        'revisionsCount' => 0,
    ]);
})->with([
    'error' => ['error', 'HTTP 403 – požadavek zablokován', 'Chyba'],
    'aborted' => ['aborted', 'Parser vrátil 0 zápasů (minule 24)', 'Přerušeno'],
]);

test('shows a running import without a duration', function () {
    Import::factory()->for(selectedTeamSeason())->running()->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('imports.data.0.status', 'running')
        ->where('imports.data.0.statusLabel', 'Probíhá')
        ->where('imports.data.0.duration', null)
        ->where('imports.data.0.fixturesFound', null)
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
    Season::factory()->current()->create(['name' => '2026/27']);

    $this->get('/imports')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('imports', null)
        ->where('isImportRunning', false)
        ->where('adminSelection.season.name', '2026/27')
        ->where('adminSelection.teamSeason', null)
        ->where('adminSelection.teamSeasons', [])
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/imports')->assertRedirect('/login');
});
