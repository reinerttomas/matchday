<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * FBC Kutná Hora B's team season in the current season, which the Týmy page lists.
 */
function teamSeasonOnTeamsPage(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/2027']))
        ->create(['name' => 'FBC Kutná Hora B']);
}

test('switches off a team season\'s auto import and returns to the Týmy page showing it off', function () {
    $teamSeason = teamSeasonOnTeamsPage();

    $response = $this->from('/teams')->patch("/teams/{$teamSeason->id}", ['auto_import_enabled' => false]);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Automatický import týmu FBC Kutná Hora B je vypnutý. Kalendář dál ukazuje poslední stažený rozpis.');
    expect($teamSeason->fresh()->auto_import_enabled)->toBeFalse();
    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.autoImportEnabled', false)
        ->etc());
});

test('switches a team season\'s auto import back on', function () {
    $teamSeason = teamSeasonOnTeamsPage();
    $teamSeason->update(['auto_import_enabled' => false]);

    $this->from('/teams')->patch("/teams/{$teamSeason->id}", ['auto_import_enabled' => true])
        ->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Automatický import týmu FBC Kutná Hora B je zapnutý.');

    expect($teamSeason->fresh()->auto_import_enabled)->toBeTrue();
});

test('keeps serving the calendar of a team season whose auto import is switched off', function () {
    $teamSeason = teamSeasonOnTeamsPage();
    Fixture::factory()->for($teamSeason)->create();

    $this->patch("/teams/{$teamSeason->id}", ['auto_import_enabled' => false]);

    $this->get('/calendar/kutna-hora-b.ics')->assertOk()->assertSee('BEGIN:VEVENT');
});

test('rejects a missing or non-boolean auto import value', function (array $payload) {
    $teamSeason = teamSeasonOnTeamsPage();

    $this->from('/teams')->patch("/teams/{$teamSeason->id}", $payload)
        ->assertRedirect('/teams')
        ->assertSessionHasErrors('auto_import_enabled');

    expect($teamSeason->fresh()->auto_import_enabled)->toBeTrue();
})->with([
    'missing' => [[]],
    'not a boolean' => [['auto_import_enabled' => 'off']],
]);

test('returns 404 for an unknown team season', function () {
    $this->patch('/teams/999', ['auto_import_enabled' => false])->assertNotFound();
});

test('redirects guests to the login page', function () {
    auth()->logout();
    $teamSeason = teamSeasonOnTeamsPage();

    $this->patch("/teams/{$teamSeason->id}", ['auto_import_enabled' => false])->assertRedirect('/login');

    expect($teamSeason->fresh()->auto_import_enabled)->toBeTrue();
});
