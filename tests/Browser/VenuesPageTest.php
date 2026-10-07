<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Venue;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('previews the calendar location while the missing address is typed, then saves it', function () {
    $venue = Venue::factory()->withoutAddress()->create(['name' => 'SH Kutná Hora Klimeška']);

    visit('/venues')
        ->assertSee('Adresu nemá 1 hala.')
        ->click('@fill-in-address')
        ->assertSeeIn('@calendar-location-preview', 'SH Kutná Hora Klimeška')
        ->type('address', 'Čáslavská 274, Kutná Hora')
        ->assertSeeIn('@calendar-location-preview', 'SH Kutná Hora Klimeška, Čáslavská 274, Kutná Hora')
        ->click('@update-venue')
        ->assertSee('Adresa haly SH Kutná Hora Klimeška je uložená.')
        ->assertMissing('@update-venue')
        ->assertSee('Všechny haly mají adresu.')
        ->assertSeeIn('tbody tr:first-child', 'Čáslavská 274, Kutná Hora')
        ->assertNoJavaScriptErrors();

    expect($venue->fresh()->address)->toBe('Čáslavská 274, Kutná Hora');
});
