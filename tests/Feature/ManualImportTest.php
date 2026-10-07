<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Jobs\ImportFixtureList;
use App\Models\Import;
use App\Models\Season;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    travelTo('2026-10-07 10:00:00');
});

test('queues a manual import of the selected team season and returns to the page', function () {
    $season = Season::factory()->current()->create();
    TeamSeason::factory()->for($season)->create(['name' => 'FBC Kutná Hora B']);
    $selected = TeamSeason::factory()->for($season)->create(['name' => 'FBC Kutná Hora C']);
    $this->post('/admin-selection', ['season_id' => $season->id, 'team_season_id' => $selected->id]);
    Queue::fake([ImportFixtureList::class]);

    $response = $this->from('/fixtures')->post('/imports');

    $response->assertRedirect('/fixtures')
        ->assertInertiaFlash('toast.message', 'Stahování rozpisu bylo spuštěno.');
    Queue::assertPushedTimes(ImportFixtureList::class, 1);
    Queue::assertPushed(ImportFixtureList::class, fn (ImportFixtureList $job): bool => $job->teamSeason->is($selected));
});

test('queues a manual import of a team season outside the current season', function () {
    Season::factory()->current()->create(['name' => '2026/27']);
    $next = TeamSeason::factory()->for(Season::factory()->state(['name' => '2027/28']))->create();
    $this->post('/admin-selection', ['season_id' => $next->season_id, 'team_season_id' => $next->id]);
    Queue::fake([ImportFixtureList::class]);

    $this->from('/imports')->post('/imports')->assertRedirect('/imports');

    Queue::assertPushed(ImportFixtureList::class, fn (ImportFixtureList $job): bool => $job->teamSeason->is($next));
});

test('reports a queued manual import as running before a worker picks it up', function () {
    TeamSeason::factory()->for(Season::factory()->current())->create();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/imports');

    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('isImportRunning', true)
        ->etc());
});

test('stops reporting a queued manual import as running after 15 minutes when no worker picks it up', function () {
    TeamSeason::factory()->for(Season::factory()->current())->create();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/imports');

    travelTo('2026-10-07 10:14:59');
    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page->where('isImportRunning', true)->etc());

    travelTo('2026-10-07 10:15:00');
    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page->where('isImportRunning', false)->etc());
});

test('stops reporting the manual import as running once its job has run', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    $this->from('/fixtures')->post('/imports')->assertRedirect('/fixtures');

    expect(Import::query()->sole())
        ->team_season_id->toBe($teamSeason->id)
        ->trigger->toBe(ImportTrigger::Manual)
        ->status->toBe(ImportStatus::Ok);
    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('isImportRunning', false)
        ->where('fixtureList.isImported', true)
        ->etc());
});

test('starts no second import while an import of the team season is running', function () {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->create();
    $running = Import::factory()->for($teamSeason)->running()->create(['started_at' => now()->subMinute()]);
    Http::preventStrayRequests();

    $this->from('/fixtures')->post('/imports')->assertRedirect('/fixtures');

    expect(Import::query()->sole()->is($running))->toBeTrue();
    Http::assertNothingSent();

    // The skipped job clears the queued mark, so the page follows the running import alone.
    $running->update(['status' => ImportStatus::Ok, 'finished_at' => now()]);
    $this->get('/fixtures')->assertInertia(fn (Assert $page) => $page
        ->where('isImportRunning', false)
        ->etc());
});

test('returns 404 while the selected season has no team seasons', function () {
    Season::factory()->current()->create();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/imports')->assertNotFound();

    Queue::assertNothingPushed();
});

test('redirects guests to the login page', function () {
    auth()->logout();
    TeamSeason::factory()->for(Season::factory()->current())->create();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/imports')->assertRedirect('/login');

    Queue::assertNothingPushed();
});
