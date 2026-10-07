<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Jobs\ImportFixtureList;
use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * The season the Týmy page adds teams to by default.
 */
function seasonToAddTeamsTo(): Season
{
    return Season::factory()->current()->create(['name' => '2026/2027']);
}

/**
 * FBC Kutná Hora B, a team with a team season in 2025/2026 only, ready to be carried over.
 */
function teamFromPreviousSeason(): Team
{
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()
        ->for($team)
        ->for(Season::factory()->state(['name' => '2025/2026']))
        ->create(['name' => 'FBC Kutná Hora B', 'external_id' => 40001]);

    return $team;
}

test('adds a new team named by the administrator to the selected season with a slug derived from its name, and queues its first import', function () {
    $season = seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $response = $this->from('/teams')->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => 'FBC Kutná Hora B',
    ]);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Tým FBC Kutná Hora B je přidaný do sezony 2026/2027. Jeho rozpis se právě stahuje.');
    $team = Team::query()->sole();
    expect($team)
        ->name->toBe('FBC Kutná Hora B')
        ->slug->toBe('fbc-kutna-hora-b');
    $teamSeason = $team->teamSeasons()->sole();
    expect($teamSeason)
        ->season_id->toBe($season->id)
        ->external_id->toBe(45019)
        ->source_url->toBe('https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->name->toBeNull()
        ->competition_name->toBeNull()
        ->auto_import_enabled->toBeTrue();
    Queue::assertPushedTimes(ImportFixtureList::class, 1);
    Queue::assertPushed(ImportFixtureList::class, fn (ImportFixtureList $job): bool => $job->teamSeason->is($teamSeason));
});

test('derives a new team\'s slug from its name even when a slug is sent', function () {
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => 'FBC Kutná Hora B',
        'slug' => 'kutna-hora-b',
    ])->assertSessionHasNoErrors();

    expect(Team::query()->sole()->slug)->toBe('fbc-kutna-hora-b');
});

test('adds the team to the season the administrator picked instead of the current one', function () {
    seasonToAddTeamsTo();
    $picked = Season::factory()->create(['name' => '2027/2028']);
    $this->post('/admin-selection', ['season_id' => $picked->id]);
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => 'FBC Kutná Hora B',
    ])->assertSessionHasNoErrors();

    expect(TeamSeason::query()->sole()->season_id)->toBe($picked->id);
});

test('fills the new team season\'s name and competition by its first import, leaving the team\'s name as entered', function () {
    travelTo('2026-10-07 10:00:00');
    seasonToAddTeamsTo();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    $this->post('/teams', [
        'source_url' => Ceskyflorbal::FIXTURE_LIST_URL,
        'name' => 'Kutná Hora B',
    ])->assertSessionHasNoErrors();

    expect(TeamSeason::query()->sole())
        ->name->toBe('FBC Kutná Hora B')
        ->competition_name->toBe('PH a SČ liga mužů');
    expect(Team::query()->sole()->name)->toBe('Kutná Hora B');
    expect(Import::query()->sole())
        ->trigger->toBe(ImportTrigger::Manual)
        ->status->toBe(ImportStatus::Ok);
});

test('carries a team over from a previous season with a new team season and the same name and slug', function () {
    $season = seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    Queue::fake([ImportFixtureList::class]);

    $response = $this->from('/teams')->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
    ]);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Tým FBC Kutná Hora B je přidaný do sezony 2026/2027. Jeho rozpis se právě stahuje.');
    expect(Team::query()->sole())
        ->name->toBe('FBC Kutná Hora B')
        ->slug->toBe('kutna-hora-b');
    $teamSeason = $team->teamSeasons()->whereBelongsTo($season)->sole();
    expect($teamSeason)
        ->external_id->toBe(45019)
        ->source_url->toBe('https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->name->toBeNull()
        ->competition_name->toBeNull();
    expect($team->teamSeasons()->where('external_id', 40001)->sole()->name)->toBe('FBC Kutná Hora B');
    Queue::assertPushed(ImportFixtureList::class, fn (ImportFixtureList $job): bool => $job->teamSeason->is($teamSeason));
});

test('carries a team over without its name or slug even when they are sent', function () {
    seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => 'renamed',
    ])->assertSessionHasNoErrors();

    expect(Team::query()->sole())
        ->name->toBe('FBC Kutná Hora B')
        ->slug->toBe('kutna-hora-b');
});

test('reads the federation\'s team ID from the fixture list address and stores the address in its usual form', function (string $address) {
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', ['source_url' => $address, 'name' => 'FBC Kutná Hora B'])->assertSessionHasNoErrors();

    expect(TeamSeason::query()->sole())
        ->external_id->toBe(45019)
        ->source_url->toBe('https://www.ceskyflorbal.cz/team/detail/matches/45019');
})->with([
    'without www' => ['https://ceskyflorbal.cz/team/detail/matches/45019'],
    'over http' => ['http://www.ceskyflorbal.cz/team/detail/matches/45019'],
    'without a scheme' => ['www.ceskyflorbal.cz/team/detail/matches/45019'],
    'with a trailing slash' => ['https://www.ceskyflorbal.cz/team/detail/matches/45019/'],
    'with a query' => ['https://www.ceskyflorbal.cz/team/detail/matches/45019?season=2026'],
]);

test('rejects an address that is not a team\'s fixture list on ceskyflorbal.cz', function (?string $address, string $message) {
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', ['source_url' => $address, 'name' => 'FBC Kutná Hora B'])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['source_url' => $message]);

    expect(Team::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
})->with([
    'missing' => [null, 'Zadejte adresu rozpisu zápasů týmu na ceskyflorbal.cz.'],
    'another site' => ['https://www.example.com/team/detail/matches/45019', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
    'a look-alike domain' => ['https://www.ceskyflorbal.cz.example.com/team/detail/matches/45019', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
    'the team overview' => ['https://www.ceskyflorbal.cz/team/detail/overview/45019', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
    'a match detail' => ['https://www.ceskyflorbal.cz/match/detail/default/1306754', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
    'without a team ID' => ['https://www.ceskyflorbal.cz/team/detail/matches/', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
    'not an address' => ['rozpis B týmu', 'Adresa musí vést na rozpis zápasů týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/matches/45019.'],
]);

test('rejects a fixture list that another team season already has', function () {
    seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/40001',
    ])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['source_url' => 'Tento rozpis už patří týmu FBC Kutná Hora B 2025/2026.']);

    expect(TeamSeason::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

test('rejects a new team\'s name that is missing, too long, without a slug or with a slug another team has', function (?string $name, string $message) {
    seasonToAddTeamsTo();
    Team::factory()->create(['name' => 'FBC Kutná Hora A', 'slug' => 'fbc-kutna-hora-a']);
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => $name,
    ])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['name' => $message]);

    expect(Team::query()->count())->toBe(1)
        ->and(TeamSeason::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
})->with([
    'missing' => [null, 'Zadejte název týmu.'],
    'too long' => [str_repeat('a', 101), 'Název týmu může mít nejvýše 100 znaků.'],
    'without letters or digits' => ['–?!', 'Název týmu musí obsahovat aspoň jedno písmeno nebo číslici.'],
    'with a taken slug' => ['fbc kutna hora a', 'Jiný tým už má stejnou adresu kalendáře (fbc-kutna-hora-a). Zvolte jiný název.'],
]);

test('rejects carrying over a team that is unknown', function () {
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', [
        'team_id' => 999,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
    ])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['team_id' => 'Vybraný tým neexistuje.']);

    expect(TeamSeason::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
});

test('rejects carrying over a team that already is in the selected season', function () {
    $season = seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    TeamSeason::factory()->for($team)->for($season)->create(['external_id' => 45000]);
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
    ])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['team_id' => 'Tento tým už v sezoně 2026/2027 je.']);

    expect(TeamSeason::query()->count())->toBe(2);
    Queue::assertNothingPushed();
});

test('returns 404 while there is no season', function () {
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => 'FBC Kutná Hora B',
    ])->assertNotFound();

    expect(Team::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
});

test('redirects guests to the login page', function () {
    auth()->logout();
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'name' => 'FBC Kutná Hora B',
    ])->assertRedirect('/login');

    expect(Team::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
});
