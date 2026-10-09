<?php

declare(strict_types=1);

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\TeamSeason;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('opens a revised fixture\'s revisions under its row from the "Změněno" badge, one row at a time', function () {
    $teamSeason = TeamSeason::factory()->for(Season::factory()->current())->create();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => now()->subDay()]);
    $moved = Fixture::factory()->for($teamSeason)->create(['date' => today()->addDays(3)]);
    $added = Fixture::factory()->for($teamSeason)->create(['date' => today()->addDays(4)]);
    Revision::factory()->for($import)->for($moved)->fieldChange(RevisionField::Time, null, '19:00')->create();
    Revision::factory()->for($import)->for($moved)->fieldChange(RevisionField::Venue, 'Hala Kolín', 'Sportovní hala Kutná Hora')->create();
    Revision::factory()->for($import)->for($added)->fixtureAdded()->create();
    $movedToggle = 'li:first-child > div > [data-test=toggle-revisions]';
    $addedToggle = 'li:nth-child(2) > div > [data-test=toggle-revisions]';

    visit('/fixtures')
        ->assertSeeIn($movedToggle, '2')
        ->assertSeeIn($addedToggle, '1')
        ->assertAttribute($movedToggle, 'aria-expanded', 'false')
        ->assertMissing('@fixture-revisions')
        ->click($movedToggle)
        ->assertAttribute($movedToggle, 'aria-expanded', 'true')
        ->assertAttribute($addedToggle, 'aria-expanded', 'false')
        ->assertSeeIn('@fixture-revisions', 'Hala Kolín')
        ->assertSeeIn('[data-test=fixture-revisions] s', 'TBD')
        ->assertDontSee('Nový zápas v rozpisu')
        ->click($addedToggle)
        ->assertSee('Nový zápas v rozpisu')
        ->click($movedToggle)
        ->assertAttribute($movedToggle, 'aria-expanded', 'false')
        ->assertDontSee('Hala Kolín')
        ->assertNoJavaScriptErrors();
});
