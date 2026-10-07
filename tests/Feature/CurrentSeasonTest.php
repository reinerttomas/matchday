<?php

declare(strict_types=1);

use App\Enums\ImportTrigger;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('marks a season as current and the previously current one stops being current', function () {
    $previous = Season::factory()->current()->create(['name' => '2026/27']);
    $next = Season::factory()->create(['name' => '2027/28']);
    $older = Season::factory()->create(['name' => '2025/26']);

    $response = $this->from('/seasons')->post("/seasons/{$next->id}/current");

    $response->assertRedirect('/seasons')
        ->assertInertiaFlash('toast.message', 'Sezona 2027/28 je aktuální. Kalendáře, veřejné stránky a automatické importy teď používají její týmy.');
    expect($next->fresh()->is_current)->toBeTrue()
        ->and($previous->fresh()->is_current)->toBeFalse()
        ->and($older->fresh()->is_current)->toBeFalse();
    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('seasons.0.isCurrent', true)
        ->where('seasons.1.isCurrent', false)
        ->where('seasons.2.isCurrent', false)
        ->etc());
});

test('keeps exactly one current season when the current season is marked again', function () {
    $current = Season::factory()->current()->create(['name' => '2026/27']);
    Season::factory()->create(['name' => '2027/28']);

    $this->from('/seasons')->post("/seasons/{$current->id}/current")->assertRedirect('/seasons');

    expect(Season::query()->where('is_current', true)->pluck('id')->all())->toBe([$current->id]);
});

test('marks a season as current while there is no current season', function () {
    $season = Season::factory()->create(['name' => '2026/27']);

    $this->post("/seasons/{$season->id}/current");

    expect(Season::query()->where('is_current', true)->pluck('id')->all())->toBe([$season->id]);
});

test('switches the calendar and the public page to the team season of the new current season', function () {
    $team = Team::factory()->create(['slug' => 'kutna-hora-b']);
    $previousTeamSeason = TeamSeason::factory()->for($team)->for(Season::factory()->current()->state(['name' => '2026/27']))->create(['name' => 'FBC Kutná Hora B']);
    $nextTeamSeason = TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2027/28']))->create(['name' => 'FBC Kutná Hora']);
    $previousFixture = Fixture::factory()->for($previousTeamSeason)->create();
    $nextFixture = Fixture::factory()->for($nextTeamSeason)->create();

    $this->post("/seasons/{$nextTeamSeason->season_id}/current");

    expect($this->get('/calendar/kutna-hora-b.ics')->getContent())
        ->toContain("UID:matchday-fixture-{$nextFixture->id}\r\n")
        ->not->toContain("UID:matchday-fixture-{$previousFixture->id}\r\n");
    $this->get('/t/kutna-hora-b')->assertInertia(fn (Assert $page) => $page
        ->where('season', '2027/28')
        ->where('teamSeason.name', 'FBC Kutná Hora')
        ->etc());
});

test('makes scheduled imports cover the team seasons of the new current season only', function () {
    TeamSeason::factory()->for(Season::factory()->current()->state(['name' => '2025/26']))->create();
    $nextTeamSeason = TeamSeason::factory()->for(Season::factory()->state(['name' => '2026/27']))->notImported()->create([
        'external_id' => 45019,
        'source_url' => Ceskyflorbal::FIXTURE_LIST_URL,
    ]);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    $this->post("/seasons/{$nextTeamSeason->season_id}/current");
    artisan('fixtures:import')->assertSuccessful();

    expect(Import::query()->get(['team_season_id', 'trigger'])->toArray())->toBe([
        ['team_season_id' => $nextTeamSeason->id, 'trigger' => ImportTrigger::Schedule->value],
    ]);
});

test('keeps the season the administrator works on, whether picked or shown by default', function (bool $isPicked) {
    $current = Season::factory()->current()->create(['name' => '2026/27']);
    $teamSeason = TeamSeason::factory()->for($current)->create();
    $next = Season::factory()->create(['name' => '2027/28']);

    if ($isPicked) {
        $this->post('/admin-selection', ['season_id' => $current->id]);
    }

    $this->post("/seasons/{$next->id}/current");

    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season', ['id' => $current->id, 'name' => '2026/27', 'isCurrent' => false])
        ->where('adminSelection.teamSeason.id', $teamSeason->id)
        ->etc());
})->with([
    'picked' => true,
    'shown by default' => false,
]);

test('returns 404 for an unknown season', function () {
    $current = Season::factory()->current()->create();

    $this->post('/seasons/999/current')->assertNotFound();

    expect($current->fresh()->is_current)->toBeTrue();
});

test('redirects guests to the login page', function () {
    auth()->logout();
    $current = Season::factory()->current()->create(['name' => '2026/27']);
    $next = Season::factory()->create(['name' => '2027/28']);

    $this->post("/seasons/{$next->id}/current")->assertRedirect('/login');

    expect($current->fresh()->is_current)->toBeTrue()
        ->and($next->fresh()->is_current)->toBeFalse();
});
