<?php

declare(strict_types=1);

use App\Enums\Category;
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
 * FBC Kutná Hora B, playing the current season 2026/2027 under the name ceskyflorbal.cz gives it.
 */
function teamToRename(): Team
{
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for(Season::factory()->current()->state(['name' => '2026/2027']))->create(['name' => 'Florbal Kutná Hora B']);

    return $team;
}

test('renames a team, keeps its slug and returns to the Týmy page naming it by its new name', function () {
    $team = teamToRename();

    $response = $this->from('/teams')->patch("/teams/{$team->id}/name", ['name' => 'FBC Sokol Kutná Hora B', 'category' => 'muzi']);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Tým FBC Sokol Kutná Hora B je upravený. Adresa kalendáře zůstává stejná.');
    expect($team->fresh())
        ->name->toBe('FBC Sokol Kutná Hora B')
        ->slug->toBe('kutna-hora-b')
        ->and($team->teamSeasons()->sole()->name)->toBe('Florbal Kutná Hora B');
});

test('changes a team\'s category and keeps its slug', function () {
    $team = teamToRename();

    $response = $this->from('/teams')->patch("/teams/{$team->id}/name", ['name' => 'FBC Kutná Hora B', 'category' => 'zeny']);

    $response->assertRedirect('/teams')->assertSessionHasNoErrors();
    expect($team->fresh())
        ->name->toBe('FBC Kutná Hora B')
        ->category->toBe(Category::Women)
        ->slug->toBe('kutna-hora-b');
    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.category', ['value' => 'zeny', 'label' => 'ženy'])
        ->etc());
});

test('shows the new name in the team list of every season the team plays', function () {
    $team = teamToRename();
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    TeamSeason::factory()->for($team)->for($previousSeason)->create();

    $this->patch("/teams/{$team->id}/name", ['name' => 'FBC Sokol Kutná Hora B', 'category' => 'muzi']);

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.name', 'FBC Sokol Kutná Hora B')
        ->etc());
    $this->post('/admin-selection', ['season_id' => $previousSeason->id]);
    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.name', 'FBC Sokol Kutná Hora B')
        ->etc());
});

test('serves the calendar under the new name at the same address', function () {
    $team = teamToRename();
    Fixture::factory()->for($team->teamSeasons()->sole())->home()->create(['opponent_name' => 'Tatran Střešovice C']);

    $this->patch("/teams/{$team->id}/name", ['name' => 'FBC Sokol Kutná Hora B', 'category' => 'muzi']);

    // Lines longer than 75 octets are folded onto continuation lines that start with a space.
    $calendar = str_replace("\r\n ", '', (string) $this->get('/calendar/kutna-hora-b.ics')->assertOk()->getContent());
    expect($calendar)
        ->toContain('X-WR-CALNAME:FBC Sokol Kutná Hora B')
        ->toContain('SUMMARY:FBC Sokol Kutná Hora B – Tatran Střešovice C');
});

test('accepts a name whose slug another team already has, because the slug stays the same', function () {
    $team = teamToRename();
    Team::factory()->create(['name' => 'FBC Kutná Hora A', 'slug' => 'fbc-kutna-hora-a']);

    $this->patch("/teams/{$team->id}/name", ['name' => 'FBC Kutná Hora A', 'category' => 'muzi'])->assertSessionHasNoErrors();

    expect($team->fresh())
        ->name->toBe('FBC Kutná Hora A')
        ->slug->toBe('kutna-hora-b');
});

test('rejects a missing, non-text or too long name', function (array $payload, string $message) {
    $team = teamToRename();

    $this->from('/teams')->patch("/teams/{$team->id}/name", $payload)
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['name' => $message]);

    expect($team->fresh()->name)->toBe('FBC Kutná Hora B');
})->with([
    'missing' => [['name' => '', 'category' => 'muzi'], 'Zadejte název týmu.'],
    'not text' => [['name' => ['FBC Sokol Kutná Hora B'], 'category' => 'muzi'], 'Název týmu musí být text.'],
    'too long' => [['name' => str_repeat('a', 101), 'category' => 'muzi'], 'Název týmu může mít nejvýše 100 znaků.'],
]);

test('rejects a missing or unknown category', function (array $payload, string $message) {
    $team = teamToRename();

    $this->from('/teams')->patch("/teams/{$team->id}/name", ['name' => 'FBC Kutná Hora B', ...$payload])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['category' => $message]);

    expect($team->fresh()->category)->toBe(Category::Men);
})->with([
    'missing' => [[], 'Vyberte kategorii týmu.'],
    'unknown' => [['category' => 'veterani'], 'Vyberte kategorii ze seznamu.'],
]);

test('returns 404 for an unknown team', function () {
    $this->patch('/teams/999/name', ['name' => 'FBC Sokol Kutná Hora B', 'category' => 'muzi'])->assertNotFound();
});

test('redirects guests to the login page', function () {
    auth()->logout();
    $team = teamToRename();

    $this->patch("/teams/{$team->id}/name", ['name' => 'FBC Sokol Kutná Hora B', 'category' => 'muzi'])->assertRedirect('/login');

    expect($team->fresh()->name)->toBe('FBC Kutná Hora B');
});
