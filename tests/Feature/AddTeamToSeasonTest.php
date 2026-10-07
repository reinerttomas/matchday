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
    return Season::factory()->current()->create(['name' => '2026/27']);
}

/**
 * FBC Kutná Hora B, a team with a team season in 2025/26 only, ready to be carried over.
 */
function teamFromPreviousSeason(): Team
{
    $team = Team::factory()->create(['slug' => 'kutna-hora-b']);
    TeamSeason::factory()
        ->for($team)
        ->for(Season::factory()->state(['name' => '2025/26']))
        ->create(['name' => 'FBC Kutná Hora B', 'external_id' => 40001]);

    return $team;
}

test('adds a new team to the selected season and queues its first import', function () {
    $season = seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $response = $this->from('/teams')->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => 'kutna-hora-b',
    ]);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Tým kutna-hora-b je přidaný do sezony 2026/27. Jeho rozpis se právě stahuje.');
    $teamSeason = Team::query()->where('slug', 'kutna-hora-b')->sole()->teamSeasons()->sole();
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

test('adds the team to the season the administrator picked instead of the current one', function () {
    seasonToAddTeamsTo();
    $picked = Season::factory()->create(['name' => '2027/28']);
    $this->post('/admin-selection', ['season_id' => $picked->id]);
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => 'kutna-hora-b',
    ])->assertSessionHasNoErrors();

    expect(TeamSeason::query()->sole()->season_id)->toBe($picked->id);
});

test('fills the new team season\'s name and competition by its first import', function () {
    travelTo('2026-10-07 10:00:00');
    seasonToAddTeamsTo();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    $this->post('/teams', [
        'source_url' => Ceskyflorbal::FIXTURE_LIST_URL,
        'slug' => 'kutna-hora-b',
    ])->assertSessionHasNoErrors();

    expect(TeamSeason::query()->sole())
        ->name->toBe('FBC Kutná Hora B')
        ->competition_name->toBe('PH a SČ liga mužů');
    expect(Import::query()->sole())
        ->trigger->toBe(ImportTrigger::Manual)
        ->status->toBe(ImportStatus::Ok);
});

test('carries a team over from a previous season with a new team season and the same slug', function () {
    $season = seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    Queue::fake([ImportFixtureList::class]);

    $response = $this->from('/teams')->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
    ]);

    $response->assertRedirect('/teams')
        ->assertInertiaFlash('toast.message', 'Tým kutna-hora-b je přidaný do sezony 2026/27. Jeho rozpis se právě stahuje.');
    expect(Team::query()->sole()->slug)->toBe('kutna-hora-b');
    $teamSeason = $team->teamSeasons()->whereBelongsTo($season)->sole();
    expect($teamSeason)
        ->external_id->toBe(45019)
        ->source_url->toBe('https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->name->toBeNull()
        ->competition_name->toBeNull();
    expect($team->teamSeasons()->where('external_id', 40001)->sole()->name)->toBe('FBC Kutná Hora B');
    Queue::assertPushed(ImportFixtureList::class, fn (ImportFixtureList $job): bool => $job->teamSeason->is($teamSeason));
});

test('carries a team over without its slug even when a slug is sent', function () {
    seasonToAddTeamsTo();
    $team = teamFromPreviousSeason();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'team_id' => $team->id,
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => 'renamed',
    ])->assertSessionHasNoErrors();

    expect(Team::query()->sole()->slug)->toBe('kutna-hora-b');
});

test('reads the federation\'s team ID from the fixture list address and stores the address in its usual form', function (string $address) {
    seasonToAddTeamsTo();
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', ['source_url' => $address, 'slug' => 'kutna-hora-b'])->assertSessionHasNoErrors();

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

    $this->from('/teams')->post('/teams', ['source_url' => $address, 'slug' => 'kutna-hora-b'])
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
        ->assertSessionHasErrors(['source_url' => 'Tento rozpis už patří týmu FBC Kutná Hora B 2025/26.']);

    expect(TeamSeason::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

test('rejects a new team\'s slug that is missing, not URL-safe, too long or taken', function (?string $slug, string $message) {
    seasonToAddTeamsTo();
    Team::factory()->create(['slug' => 'kutna-hora-a']);
    Queue::fake([ImportFixtureList::class]);

    $this->from('/teams')->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => $slug,
    ])
        ->assertRedirect('/teams')
        ->assertSessionHasErrors(['slug' => $message]);

    expect(Team::query()->count())->toBe(1)
        ->and(TeamSeason::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
})->with([
    'missing' => [null, 'Zadejte slug.'],
    'with capital letters' => ['Kutna-Hora-B', 'Slug smí obsahovat jen malá písmena bez diakritiky a číslice, oddělené pomlčkou, např. kutna-hora-b.'],
    'with a space' => ['kutna hora b', 'Slug smí obsahovat jen malá písmena bez diakritiky a číslice, oddělené pomlčkou, např. kutna-hora-b.'],
    'with a leading dash' => ['-kutna-hora-b', 'Slug smí obsahovat jen malá písmena bez diakritiky a číslice, oddělené pomlčkou, např. kutna-hora-b.'],
    'with a double dash' => ['kutna--hora-b', 'Slug smí obsahovat jen malá písmena bez diakritiky a číslice, oddělené pomlčkou, např. kutna-hora-b.'],
    'too long' => [str_repeat('a', 101), 'Slug může mít nejvýše 100 znaků.'],
    'taken' => ['kutna-hora-a', 'Slug kutna-hora-a už používá jiný tým.'],
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
        ->assertSessionHasErrors(['team_id' => 'Tento tým už v sezoně 2026/27 je.']);

    expect(TeamSeason::query()->count())->toBe(2);
    Queue::assertNothingPushed();
});

test('returns 404 while there is no season', function () {
    Queue::fake([ImportFixtureList::class]);

    $this->post('/teams', [
        'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        'slug' => 'kutna-hora-b',
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
        'slug' => 'kutna-hora-b',
    ])->assertRedirect('/login');

    expect(Team::query()->exists())->toBeFalse();
    Queue::assertNothingPushed();
});
