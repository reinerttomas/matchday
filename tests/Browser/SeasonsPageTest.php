<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('creates a season from the dialog, which closes and the list shows the new season', function () {
    Season::factory()->current()->create(['name' => '2026/27']);

    visit('/seasons')
        ->click('@create-season')
        ->fill('name', '2027/28')
        ->click('@store-season')
        ->assertSee('Sezona 2027/28 je vytvořená.')
        ->assertMissing('@store-season')
        ->assertSeeIn('tbody tr:first-child', '2027/28')
        ->assertNoJavaScriptErrors();

    expect(Season::query()->where('name', '2027/28')->exists())->toBeTrue();
});

test('shows the validation message in the dialog while it stays open', function () {
    Season::factory()->current()->create(['name' => '2026/27']);

    visit('/seasons')
        ->click('@create-season')
        ->fill('name', '2027/29')
        ->click('@store-season')
        ->assertSee('Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.')
        ->assertVisible('@store-season')
        ->assertNoJavaScriptErrors();

    expect(Season::query()->count())->toBe(1);
});

test('marks a season as current after the administrator confirms it', function () {
    $previous = Season::factory()->current()->create(['name' => '2026/27']);
    $next = Season::factory()->create(['name' => '2027/28']);

    visit('/seasons')
        ->click('@mark-season-as-current')
        ->assertSee('Nastavit sezonu 2027/28 jako aktuální?')
        ->click('@confirm-mark-season-as-current')
        ->assertSee('Sezona 2027/28 je aktuální.')
        ->assertSeeIn('tbody tr:first-child [data-test="current-season"]', 'Aktuální')
        ->assertCount('@current-season', 1)
        ->assertNoJavaScriptErrors();

    expect($next->fresh()->is_current)->toBeTrue()
        ->and($previous->fresh()->is_current)->toBeFalse();
});
