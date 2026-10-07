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
    Season::factory()->current()->create(['name' => '2026/2027']);

    $response = $this->from('/seasons')->post('/seasons', ['name' => '2027/2028']);

    $response->assertRedirect('/seasons')
        ->assertInertiaFlash('toast.message', 'Sezona 2027/2028 je vytvořená.');
    expect(Season::query()->where('name', '2027/2028')->sole()->is_current)->toBeFalse();
    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('seasons.0.name', '2027/2028')
        ->where('seasons.0.isCurrent', false)
        ->where('seasons.1.isCurrent', true)
        ->etc());
});

test('rejects a name that is not two consecutive years', function (array $payload, string $message) {
    Season::factory()->current()->create(['name' => '2026/2027']);

    $this->from('/seasons')->post('/seasons', $payload)
        ->assertRedirect('/seasons')
        ->assertSessionHasErrors(['name' => $message]);

    expect(Season::query()->count())->toBe(1);
})->with([
    'missing' => [[], 'Zadejte název sezony.'],
    'a single year' => [['name' => '2027'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/2028.'],
    'a shortened second year' => [['name' => '2027/28'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/2028.'],
    'years that do not follow each other' => [['name' => '2027/2029'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/2028.'],
    'a dash instead of a slash' => [['name' => '2027-28'], 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/2028.'],
]);

test('accepts a season spanning the turn of a century', function () {
    $this->post('/seasons', ['name' => '2099/2100'])->assertSessionHasNoErrors();

    expect(Season::query()->where('name', '2099/2100')->exists())->toBeTrue();
});

test('rejects the name of an existing season', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);

    $this->from('/seasons')->post('/seasons', ['name' => '2026/2027'])
        ->assertRedirect('/seasons')
        ->assertSessionHasErrors(['name' => 'Sezona 2026/2027 už existuje.']);

    expect(Season::query()->count())->toBe(1);
});

test('keeps the season the administrator picked', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    $picked = Season::factory()->create(['name' => '2025/2026']);
    $this->post('/admin-selection', ['season_id' => $picked->id]);

    $this->post('/seasons', ['name' => '2027/2028']);

    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.name', '2025/2026')
        ->etc());
});

test('keeps the season shown while there is no current one, even though the new season is newer', function () {
    $season = Season::factory()->create(['name' => '2026/2027']);
    $teamSeason = TeamSeason::factory()->for($season)->create();

    $this->post('/seasons', ['name' => '2027/2028']);

    $this->get('/seasons')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.name', '2026/2027')
        ->where('adminSelection.teamSeason.id', $teamSeason->id)
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->post('/seasons', ['name' => '2027/2028'])->assertRedirect('/login');

    expect(Season::query()->exists())->toBeFalse();
});
