<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    travelTo('2026-10-06 12:00:00');
});

/**
 * The season the Týmy page shows by default.
 */
function seasonOnTeamsPage(): Season
{
    return Season::factory()->current()->create(['name' => '2026/27']);
}

test('lists the selected season\'s team seasons by name with what the administrator needs at a glance', function () {
    $season = seasonOnTeamsPage();
    $teamSeason = TeamSeason::factory()
        ->for($season)
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->create([
            'name' => 'FBC Kutná Hora B',
            'competition_name' => '2. liga mužů, skupina 3',
            'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/42001',
        ]);
    TeamSeason::factory()->for($season)->create(['name' => 'FBC Kutná Hora A']);
    TeamSeason::factory()->for(Season::factory()->state(['name' => '2025/26']))->create(['name' => 'FBC Kutná Hora C']);

    $response = $this->get('https://matchday.cz/teams');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('teams/index')
        ->has('teamSeasons', 2)
        ->where('teamSeasons.0.name', 'FBC Kutná Hora A')
        ->where('teamSeasons.1', [
            'id' => $teamSeason->id,
            'name' => 'FBC Kutná Hora B',
            'competition' => '2. liga mužů, skupina 3',
            'slug' => 'kutna-hora-b',
            'autoImportEnabled' => true,
            'lastImport' => null,
            'calendarUrl' => 'https://matchday.cz/calendar/kutna-hora-b.ics',
            'publicPageUrl' => 'https://matchday.cz/t/kutna-hora-b',
            'sourceUrl' => 'https://www.ceskyflorbal.cz/team/detail/matches/42001',
        ]));
});

test('lists the team seasons of a season the administrator picked instead of the current one', function () {
    seasonOnTeamsPage();
    $pickedSeason = Season::factory()->create(['name' => '2025/26']);
    TeamSeason::factory()->for($pickedSeason)->create(['name' => 'FBC Kutná Hora B']);
    $this->post('/admin-selection', ['season_id' => $pickedSeason->id]);

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->has('teamSeasons', 1)
        ->where('teamSeasons.0.name', 'FBC Kutná Hora B')
        ->etc());
});

test('names a team season not imported yet by its slug, without a competition', function () {
    TeamSeason::factory()
        ->for(seasonOnTeamsPage())
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->notImported()
        ->create();

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.name', 'kutna-hora-b')
        ->where('teamSeasons.0.competition', null)
        ->where('teamSeasons.0.lastImport', null)
        ->etc());
});

test('shows a team season with auto import switched off', function () {
    TeamSeason::factory()->for(seasonOnTeamsPage())->autoImportDisabled()->create();

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.autoImportEnabled', false)
        ->etc());
});

test('shows when the last import started in Prague time, without a badge after it ended ok', function () {
    $teamSeason = TeamSeason::factory()->for(seasonOnTeamsPage())->create();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-10-05 06:00:00']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 06:00:00']);

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.lastImport', [
            'startedAt' => '6. 10. 2026 08:00',
            'failure' => null,
        ])
        ->etc());
});

test('shows a badge with the reason when the last import did not end ok', function (string $state, string $reason, string $statusLabel) {
    $teamSeason = TeamSeason::factory()->for(seasonOnTeamsPage())->create();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    Import::factory()->for($teamSeason)->{$state}($reason)->create(['started_at' => '2026-10-06 06:00:00']);

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.lastImport', [
            'startedAt' => '6. 10. 2026 08:00',
            'failure' => ['status' => $state, 'statusLabel' => $statusLabel, 'reason' => $reason],
        ])
        ->etc());
})->with([
    'error' => ['error', 'HTTP 403 – požadavek zablokován', 'Chyba'],
    'aborted' => ['aborted', 'Parser vrátil 0 zápasů (minule 24)', 'Přerušeno'],
]);

test('shows the last import that has ended while another one is running', function () {
    $teamSeason = TeamSeason::factory()->for(seasonOnTeamsPage())->create();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-06 06:00:00']);
    Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-06 11:59:00']);

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons.0.lastImport.startedAt', '6. 10. 2026 08:00')
        ->etc());
});

test('finds the last imports without a query per team season', function () {
    $season = seasonOnTeamsPage();
    Import::factory()->for(TeamSeason::factory()->for($season))->create();
    DB::enableQueryLog();
    $this->get('/teams');
    $queriesForOneTeamSeason = count(DB::getQueryLog());

    Import::factory()->count(2)->for(TeamSeason::factory()->for($season))->create();
    Import::factory()->for(TeamSeason::factory()->for($season))->error()->create();
    DB::flushQueryLog();
    $this->get('/teams');

    expect(count(DB::getQueryLog()))->toBe($queriesForOneTeamSeason);
});

test('offers the teams not in the selected season yet for carrying over, named after their latest team season', function () {
    $season = seasonOnTeamsPage();
    $previousSeason = Season::factory()->create(['name' => '2025/26']);
    $olderSeason = Season::factory()->create(['name' => '2024/25']);
    $renamed = Team::factory()->create(['slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($renamed)->for($olderSeason)->create(['name' => 'Florbal Kutná Hora B']);
    TeamSeason::factory()->for($renamed)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    $notImported = Team::factory()->create(['slug' => 'kutna-hora-a']);
    TeamSeason::factory()->for($notImported)->for($previousSeason)->notImported()->create();
    $alreadyInSeason = Team::factory()->create(['slug' => 'kutna-hora-c']);
    TeamSeason::factory()->for($alreadyInSeason)->for($previousSeason)->create();
    TeamSeason::factory()->for($alreadyInSeason)->for($season)->create();

    $this->get('/teams')->assertInertia(fn (Assert $page) => $page
        ->where('carryOverTeams', [
            ['id' => $renamed->id, 'name' => 'FBC Kutná Hora B 2025/26', 'slug' => 'kutna-hora-b'],
            ['id' => $notImported->id, 'name' => 'kutna-hora-a 2025/26', 'slug' => 'kutna-hora-a'],
        ])
        ->etc());
});

test('gives the address a new team\'s calendar gets from its slug', function () {
    seasonOnTeamsPage();

    $this->get('https://matchday.cz/teams')->assertInertia(fn (Assert $page) => $page
        ->where('calendarUrlTemplate', 'https://matchday.cz/calendar/:slug.ics')
        ->etc());
});

test('shows an empty list while the selected season has no team seasons', function () {
    seasonOnTeamsPage();

    $this->get('/teams')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons', [])
        ->etc());
});

test('shows no list while there is no season', function () {
    $this->get('/teams')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('teamSeasons', null)
        ->where('carryOverTeams', [])
        ->where('adminSelection.season', null)
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/teams')->assertRedirect('/login');
});
