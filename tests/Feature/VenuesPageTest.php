<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\TeamSeason;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('lists the venues by name with their address and number of fixtures in every season, and says how many lack an address', function () {
    $klimeska = Venue::factory()->create(['name' => 'SH Kutná Hora Klimeška', 'address' => 'Čáslavská 274, Kutná Hora']);
    $arena = Venue::factory()->withoutAddress()->create(['name' => 'Unihoc Aréna Praha']);
    $sipsi = Venue::factory()->withoutAddress()->create(['name' => 'SH Kutná Hora Šipší']);
    Fixture::factory()->count(2)->for($klimeska)->for(TeamSeason::factory()->for(Season::factory()->current()))->create();
    Fixture::factory()->for($klimeska)->for(TeamSeason::factory()->for(Season::factory()))->create();
    Fixture::factory()->for($arena)->create();

    $response = $this->get('/venues');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('venues/index')
        ->where('venues', [
            ['id' => $klimeska->id, 'name' => 'SH Kutná Hora Klimeška', 'address' => 'Čáslavská 274, Kutná Hora', 'fixturesCount' => 3],
            ['id' => $sipsi->id, 'name' => 'SH Kutná Hora Šipší', 'address' => null, 'fixturesCount' => 0],
            ['id' => $arena->id, 'name' => 'Unihoc Aréna Praha', 'address' => null, 'fixturesCount' => 1],
        ])
        ->where('missingAddressSummary', 'Adresu nemají 2 haly.')
        ->where('calendarLocationTemplate', ':venue, :address'));
});

test('lists the venues in natural order, whatever the case of their names', function () {
    Venue::factory()->create(['name' => 'Hala 10']);
    Venue::factory()->create(['name' => 'hala 2']);
    Venue::factory()->create(['name' => 'Hala 1']);

    $this->get('/venues')->assertInertia(fn (Assert $page) => $page
        ->where('venues.0.name', 'Hala 1')
        ->where('venues.1.name', 'hala 2')
        ->where('venues.2.name', 'Hala 10')
        ->etc());
});

test('says how many venues lack an address in the Czech plural form for the count', function (int $count, string $summary) {
    Venue::factory()->create();
    Venue::factory()->withoutAddress()->count($count)->create();

    $this->get('/venues')->assertInertia(fn (Assert $page) => $page
        ->where('missingAddressSummary', $summary)
        ->etc());
})->with([
    'none' => [0, 'Všechny haly mají adresu.'],
    'one' => [1, 'Adresu nemá 1 hala.'],
    'five' => [5, 'Adresu nemá 5 hal.'],
]);

test('shows an empty list while no import has stored a venue', function () {
    $this->get('/venues')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('venues', [])
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/venues')->assertRedirect('/login');
});
