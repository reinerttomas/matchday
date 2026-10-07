<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('selects the current season and its first team season by default', function () {
    Season::factory()->create(['name' => '2027/2028']);
    $current = Season::factory()->current()->create(['name' => '2026/2027']);
    TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($current)->create();
    $first = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($current)->create();

    $selection = $this->get('/imports')->inertiaProps('adminSelection');

    expect($selection['season'])->toBe(['id' => $current->id, 'name' => '2026/2027', 'isCurrent' => true])
        ->and($selection['teamSeason']['id'])->toBe($first->id);
});

test('selects the newest season when no season is current', function () {
    Season::factory()->create(['name' => '2025/2026']);
    $newest = Season::factory()->create(['name' => '2026/2027']);

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.id', $newest->id)
        ->etc());
});

test('shares the seasons newest first and the team seasons of the selected season alphabetically', function () {
    $previous = Season::factory()->create(['name' => '2025/2026']);
    $current = Season::factory()->current()->create(['name' => '2026/2027']);
    $next = Season::factory()->create(['name' => '2027/2028']);
    $kutnaHoraB = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($current)->create();
    $notImported = TeamSeason::factory()->for($current)->for(Team::factory()->state(['name' => 'FBC Kutná Hora A', 'slug' => 'kutna-hora-a']))->notImported()->create();
    TeamSeason::factory()->for($kutnaHoraB->team)->for($previous)->create();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.seasons', [
            ['id' => $next->id, 'name' => '2027/2028', 'isCurrent' => false],
            ['id' => $current->id, 'name' => '2026/2027', 'isCurrent' => true],
            ['id' => $previous->id, 'name' => '2025/2026', 'isCurrent' => false],
        ])
        ->where('adminSelection.teamSeasons', [
            ['id' => $notImported->id, 'name' => 'FBC Kutná Hora A', 'teamSlug' => 'kutna-hora-a'],
            ['id' => $kutnaHoraB->id, 'name' => 'FBC Kutná Hora B', 'teamSlug' => $kutnaHoraB->team->slug],
        ])
        ->etc());
});

test('shares the selection with the settings pages too', function () {
    $season = Season::factory()->current()->create();

    $this->get('/settings/profile')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.id', $season->id)
        ->etc());
});

test('does not share a selection with guests', function () {
    auth()->logout();
    Season::factory()->current()->create();

    $this->get('/login')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection', null)
        ->etc());
});

test('switching the season selects its first team season and returns to the same page', function () {
    $current = Season::factory()->current()->create(['name' => '2026/2027']);
    TeamSeason::factory()->for($current)->create();
    $next = Season::factory()->create(['name' => '2027/2028']);
    TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($next)->create();
    $first = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($next)->create();

    $this->from('/imports')
        ->post('/admin-selection', ['season_id' => $next->id])
        ->assertRedirect('/imports');

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.season.id', $next->id)
        ->where('adminSelection.teamSeason.id', $first->id)
        ->etc());
});

test('switching the team season keeps it across pages', function () {
    $season = Season::factory()->current()->create();
    TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($season)->create();
    $other = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($season)->create();

    $this->from('/imports')->post('/admin-selection', ['season_id' => $season->id, 'team_season_id' => $other->id]);

    $this->get('/settings/profile')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.teamSeason.id', $other->id)
        ->etc());
});

test('returns to the first page of a paginated list after switching', function () {
    $season = Season::factory()->current()->create();

    $this->from('/imports?page=3')
        ->post('/admin-selection', ['season_id' => $season->id])
        ->assertRedirect('/imports');
});

test('rejects a team season from another season and keeps the selection', function () {
    $current = Season::factory()->current()->create();
    TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora A']))->for($current)->create();
    $selected = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($current)->create();
    $fromOtherSeason = TeamSeason::factory()->for(Season::factory())->create();
    // The picked team season isn't the season's first, so keeping it can't be mistaken for the default.
    $this->post('/admin-selection', ['season_id' => $current->id, 'team_season_id' => $selected->id]);

    $this->from('/imports')
        ->post('/admin-selection', ['season_id' => $current->id, 'team_season_id' => $fromOtherSeason->id])
        ->assertRedirect('/imports')
        ->assertSessionHasErrors('team_season_id');

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.teamSeason.id', $selected->id)
        ->etc());
});

test('rejects an unknown season', function () {
    $this->from('/imports')
        ->post('/admin-selection', ['season_id' => 999])
        ->assertSessionHasErrors('season_id');
});

test('falls back to the first team season when the remembered one is gone', function () {
    $season = Season::factory()->current()->create();
    $first = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for($season)->create();
    $remembered = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($season)->create();
    $this->post('/admin-selection', ['season_id' => $season->id, 'team_season_id' => $remembered->id]);

    $remembered->delete();

    $this->get('/imports')->assertInertia(fn (Assert $page) => $page
        ->where('adminSelection.teamSeason.id', $first->id)
        ->etc());
});

test('guests cannot switch the selection', function () {
    auth()->logout();
    $season = Season::factory()->create();

    $this->post('/admin-selection', ['season_id' => $season->id])->assertRedirect('/login');
});
