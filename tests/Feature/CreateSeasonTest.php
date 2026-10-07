<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('creates a season that is not current and returns to the Sezony page listing it', function () {
    Season::factory()->current()->create(['name' => '2026/27']);

    $response = $this->from('/seasons')->post('/seasons', ['name' => '2027/28']);

    $response->assertRedirect('/seasons')
        ->assertInertiaFlash('toast.message', 'Sezona 2027/28 je vytvořená.');
    expect(Season::query()->where('name', '2027/28')->sole()->is_current)->toBeFalse();
    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('seasons.0.name', '2027/28')
        ->where('seasons.0.isCurrent', false)
        ->where('seasons.1.isCurrent', true)
        ->etc());
});

test('rejects a name that is not two consecutive years', function (array $payload, string $message) {
    Season::factory()->current()->create(['name' => '2026/27']);

    $this->from('/seasons')->post('/seasons', $payload)
        ->assertRedirect('/seasons')
        ->assertSessionHasErrors(['name' => $message]);

    expect(Season::query()->count())->toBe(1);
})->with([
    'missing' => [[], 'Zadejte název sezony.'],
    'a single year' => [['name' => '2027'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.'],
    'a full second year' => [['name' => '2027/2028'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.'],
    'years that do not follow each other' => [['name' => '2027/29'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.'],
    'a dash instead of a slash' => [['name' => '2027-28'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.'],
]);

test('accepts a season spanning the turn of a century', function () {
    $this->post('/seasons', ['name' => '2099/00'])->assertSessionHasNoErrors();

    expect(Season::query()->where('name', '2099/00')->exists())->toBeTrue();
});

test('rejects the name of an existing season', function () {
    Season::factory()->current()->create(['name' => '2026/27']);

    $this->from('/seasons')->post('/seasons', ['name' => '2026/27'])
        ->assertRedirect('/seasons')
        ->assertSessionHasErrors(['name' => 'Sezona 2026/27 už existuje.']);

    expect(Season::query()->count())->toBe(1);
});

test('keeps the season the administrator picked', function () {
    Season::factory()->current()->create(['name' => '2026/27']);
    $picked = Season::factory()->create(['name' => '2025/26']);
    $this->post('/admin-selection', ['season_id' => $picked->id]);

    $this->post('/seasons', ['name' => '2027/28']);

    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.name', '2025/26')
        ->etc());
});

test('keeps the season shown while there is no current one, even though the new season is newer', function () {
    $season = Season::factory()->create(['name' => '2026/27']);
    $teamSeason = TeamSeason::factory()->for($season)->create();

    $this->post('/seasons', ['name' => '2027/28']);

    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.name', '2026/27')
        ->where('adminSelection.teamSeason.id', $teamSeason->id)
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->post('/seasons', ['name' => '2027/28'])->assertRedirect('/login');

    expect(Season::query()->exists())->toBeFalse();
});
