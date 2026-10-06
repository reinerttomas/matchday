<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;

test('reveals the rest of the season beyond the first four match days', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();
    foreach (range(1, 6) as $week) {
        Fixture::factory()->for($teamSeason)->create(['date' => today('Europe/Prague')->addWeeks($week)->toDateString()]);
    }

    $page = visit('/t/kutna-hora-b');

    $page->assertCount('@match-day', 4)
        ->click('Zobrazit celou sezonu')
        ->assertCount('@match-day', 6)
        ->assertDontSee('Zobrazit celou sezonu')
        ->assertNoJavaScriptErrors();
});

test('fits a phone screen without horizontal scrolling', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create(['name' => 'FBC Kutná Hora B', 'competition_name' => '2. liga mužů, skupina 3']);
    Fixture::factory()->for($teamSeason)->rescheduled()->postponed()->create([
        'opponent_name' => 'Florbalový klub Tatran Střešovice Praha – juniorský výběr C',
        'date' => today('Europe/Prague')->addDay()->toDateString(),
    ]);
    Fixture::factory()->for($teamSeason)->tbdTime()->create(['date' => today('Europe/Prague')->addDay()->toDateString()]);

    $page = visit('/t/kutna-hora-b')->on()->mobile();

    $page->assertSee('FBC Kutná Hora B')
        ->assertSee('Odloženo')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
});
