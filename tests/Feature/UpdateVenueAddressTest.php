<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use App\Models\Venue;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('fills in a venue\'s address and returns to the Haly page', function () {
    $venue = Venue::factory()->withoutAddress()->create(['name' => 'SH Kutná Hora Klimeška']);

    $response = $this->from('/venues')->patch("/venues/{$venue->id}", ['address' => 'Čáslavská 274, Kutná Hora']);

    $response->assertRedirect('/venues')
        ->assertInertiaFlash('toast.message', 'Adresa haly SH Kutná Hora Klimeška je uložená.');
    expect($venue->fresh()->address)->toBe('Čáslavská 274, Kutná Hora');
});

test('changes only the address, never the name the federation gives the venue', function () {
    $venue = Venue::factory()->create(['name' => 'SH Kutná Hora Klimeška', 'address' => 'Čáslavská 1, Kutná Hora']);

    $this->patch("/venues/{$venue->id}", ['name' => 'Klimeška', 'address' => 'Čáslavská 274, Kutná Hora'])
        ->assertSessionHasNoErrors();

    expect($venue->fresh())
        ->name->toBe('SH Kutná Hora Klimeška')
        ->address->toBe('Čáslavská 274, Kutná Hora');
});

test('puts the edited address into the location of the venue\'s calendar events on the next feed request', function () {
    $venue = Venue::factory()->withoutAddress()->create(['name' => 'SH Kutná Hora Klimeška']);
    Fixture::factory()->for($venue)->for(TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()))
        ->create();

    $this->patch("/venues/{$venue->id}", ['address' => 'Čáslavská 274, Kutná Hora']);

    // Lines longer than 75 octets are folded onto continuation lines that start with a space.
    $calendar = str_replace("\r\n ", '', (string) $this->get('/calendar/kutna-hora-b.ics')->getContent());
    expect($calendar)->toContain('LOCATION:SH Kutná Hora Klimeška\, Čáslavská 274\, Kutná Hora');
});

test('rejects a missing or too long address', function (array $payload, string $message) {
    $venue = Venue::factory()->create(['address' => 'Čáslavská 274, Kutná Hora']);

    $this->from('/venues')->patch("/venues/{$venue->id}", $payload)
        ->assertRedirect('/venues')
        ->assertSessionHasErrors(['address' => $message]);

    expect($venue->fresh()->address)->toBe('Čáslavská 274, Kutná Hora');
})->with([
    'missing' => [['address' => ''], 'Zadejte adresu haly.'],
    'too long' => [['address' => str_repeat('a', 256)], 'Adresa může mít nejvýše 255 znaků.'],
]);

test('redirects guests to the login page', function () {
    auth()->logout();
    $venue = Venue::factory()->withoutAddress()->create();

    $this->patch("/venues/{$venue->id}", ['address' => 'Čáslavská 274, Kutná Hora'])->assertRedirect('/login');

    expect($venue->fresh()->address)->toBeNull();
});
