<?php

declare(strict_types=1);

use App\Enums\Category;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\freezeTime;

/**
 * The lookup address of the federation team page the snapshot was saved from, with any other query parameters.
 *
 * @param  array<string, mixed>  $parameters
 */
function lookupUrl(array $parameters = []): string
{
    return '/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019', ...$parameters]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('reads the team\'s name, competition and season from the federation team page of any tab', function (string $address) {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => $address]))
        ->assertOk()
        ->assertExactJson([
            'externalId' => 45019,
            'name' => 'FBC Kutná Hora B',
            'competition' => 'PH a SČ liga mužů',
            'category' => ['value' => 'muzi', 'label' => 'muži'],
            'carryOverTeamId' => null,
            'season' => '2026/2027',
            'overviewUrl' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019',
        ]);
})->with([
    'overview' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019',
    'matches' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
    'without scheme' => 'www.ceskyflorbal.cz/team/detail/overview/45019',
    'without scheme and www' => 'ceskyflorbal.cz/team/detail/roster/45019',
]);

test('downloads the fixture list whatever tab of the federation team page was pasted', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.ceskyflorbal.cz/team/detail/matches/45019');
});

test('leaves the name and competition empty while the federation team page lists no fixtures', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotWithoutFixtures());

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertOk()
        ->assertExactJson([
            'externalId' => 45019,
            'name' => null,
            'competition' => null,
            'category' => null,
            'carryOverTeamId' => null,
            'season' => '2026/2027',
            'overviewUrl' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019',
        ]);
});

test('guesses the team\'s category from the competition it plays', function (string $competition, Category $category) {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotOf('FBC VHST Kutná Hora', $competition));

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('competition', $competition)
        ->assertJsonPath('category', ['value' => $category->value, 'label' => $category->label()]);
})->with([
    'women' => ['1. liga žen', Category::Women],
    'women, nominative' => ['Ženy - Divize 4', Category::Women],
    'juniors' => ['2. liga juniorů - skupina 3', Category::Juniors],
    'juniors, nominative' => ['Junioři - Pohár', Category::Juniors],
    'junior women' => ['Extraliga juniorek', Category::JuniorWomen],
    'junior women, nominative' => ['Juniorky - Regionální liga', Category::JuniorWomen],
    'youths' => ['1. liga dorostenců', Category::Youths],
    'youths, nominative' => ['Dorostenci - Regionální liga', Category::Youths],
    'youth women' => ['Liga dorostenek', Category::YouthWomen],
    'youth women, nominative' => ['Dorostenky - Pohár', Category::YouthWomen],
    'older boys' => ['Liga starších žáků - skupina B', Category::OlderBoys],
    'older boys, nominative' => ['Starší žáci - Regionální liga', Category::OlderBoys],
    'older girls' => ['Liga starších žákyň - skupina A', Category::OlderGirls],
    'older girls, nominative' => ['Starší žákyně - Pohár', Category::OlderGirls],
    'younger boys' => ['Liga mladších žáků - skupina C', Category::YoungerBoys],
    'younger boys, nominative' => ['Mladší žáci - Regionální liga', Category::YoungerBoys],
    'younger girls' => ['Liga mladších žákyň', Category::YoungerGirls],
    'younger girls, nominative' => ['Mladší žákyně - Pohár', Category::YoungerGirls],
    'elves' => ['Turnaj elévů', Category::Elves],
    'elves, nominative' => ['Elévové - Regionální liga', Category::Elves],
    'preparatory, plural genitive' => ['Turnaj přípravek', Category::Preparatory],
    'preparatory, singular genitive' => ['Turnaj přípravky', Category::Preparatory],
    'preparatory, nominative' => ['Přípravka - Pohár', Category::Preparatory],
    'no category word' => ['Národní liga', Category::Men],
]);

test('picks the team to carry over by the name the federation gave it last season, even when the team was renamed', function () {
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'Kutná Hora béčko', 'category' => Category::Men]);
    TeamSeason::factory()->for($team)->for($previousSeason)->create(['name' => '  fbc   KUTNÁ hora b ', 'competition_name' => 'PH a SČ liga mužů']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('carryOverTeamId', $team->id);
});

test('picks the women\'s team to carry over between a same-named men\'s and women\'s team', function () {
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    Season::factory()->current()->create(['name' => '2026/2027']);
    $men = Team::factory()->create(['name' => 'FBC VHST Kutná Hora', 'slug' => 'fbc-vhst-kutna-hora', 'category' => Category::Men]);
    $women = Team::factory()->create(['name' => 'FBC VHST Kutná Hora', 'slug' => 'fbc-vhst-kutna-hora-zeny', 'category' => Category::Women]);
    TeamSeason::factory()->for($men)->for($previousSeason)->create(['name' => 'FBC VHST Kutná Hora', 'competition_name' => 'Národní liga']);
    TeamSeason::factory()->for($women)->for($previousSeason)->create(['name' => 'FBC VHST Kutná Hora', 'competition_name' => '1. liga žen']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotOf('FBC VHST Kutná Hora', '1. liga žen'));

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('category.value', 'zeny')
        ->assertJsonPath('carryOverTeamId', $women->id);
});

test('picks no team to carry over when two teams of the same category share the federation name', function () {
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    Season::factory()->current()->create(['name' => '2026/2027']);
    foreach (['Kutná Hora B', 'Kutná Hora béčko'] as $name) {
        TeamSeason::factory()
            ->for(Team::factory()->state(['name' => $name, 'category' => Category::Men]))
            ->for($previousSeason)
            ->create(['name' => 'FBC Kutná Hora B']);
    }
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('carryOverTeamId', null);
});

test('picks no team to carry over by a federation name it had only before its latest team season', function () {
    $olderSeason = Season::factory()->create(['name' => '2024/2025']);
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'category' => Category::Men]);
    TeamSeason::factory()->for($team)->for($previousSeason)->create(['name' => 'FBC Kutná Hora C']);
    TeamSeason::factory()->for($team)->for($olderSeason)->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('carryOverTeamId', null);
});

test('picks no team to carry over that already plays the season the team is added to', function () {
    $currentSeason = Season::factory()->current()->create(['name' => '2026/2027']);
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'category' => Category::Men]))
        ->for($currentSeason)
        ->create(['name' => 'FBC Kutná Hora B', 'external_id' => 45020]);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson(lookupUrl())
        ->assertOk()
        ->assertJsonPath('carryOverTeamId', null);
});

test('picks the team to carry over by the category the administrator chose instead of the guess, which it still answers', function () {
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    Season::factory()->current()->create(['name' => '2026/2027']);
    $men = Team::factory()->create(['name' => 'FBC VHST Kutná Hora', 'slug' => 'fbc-vhst-kutna-hora', 'category' => Category::Men]);
    $women = Team::factory()->create(['name' => 'FBC VHST Kutná Hora', 'slug' => 'fbc-vhst-kutna-hora-zeny', 'category' => Category::Women]);
    TeamSeason::factory()->for($men)->for($previousSeason)->create(['name' => 'FBC VHST Kutná Hora']);
    TeamSeason::factory()->for($women)->for($previousSeason)->create(['name' => 'FBC VHST Kutná Hora']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotOf('FBC VHST Kutná Hora', 'Národní liga'));

    $this->getJson(lookupUrl(['category' => 'zeny']))
        ->assertOk()
        ->assertJsonPath('category', ['value' => 'muzi', 'label' => 'muži'])
        ->assertJsonPath('carryOverTeamId', $women->id);
});

test('guesses no category and picks no team to carry over while the page lists no fixtures, whatever the chosen category', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['category' => Category::Men]))
        ->for(Season::factory()->state(['name' => '2025/2026']))
        ->create();
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotWithoutFixtures());

    $this->getJson(lookupUrl(['category' => 'muzi']))
        ->assertOk()
        ->assertJsonPath('category', null)
        ->assertJsonPath('carryOverTeamId', null);
});

test('rejects a category that is not one of the categories without downloading anything', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Http::preventStrayRequests();

    $this->getJson(lookupUrl(['category' => 'veterani']))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['category' => 'Vyberte kategorii ze seznamu.']);
});

test('rejects an address that is not a federation team page without downloading anything', function (?string $address, string $message) {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Http::preventStrayRequests();

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => $address]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['federation_team_page_url' => $message]);
})->with([
    'another site' => ['https://www.example.com/team/detail/overview/45019', 'Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019.'],
    'a competition page' => ['https://www.ceskyflorbal.cz/competition/default/detail/4574', 'Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019.'],
    'missing' => [null, 'Zadejte adresu stránky týmu na ceskyflorbal.cz.'],
]);

test('rejects a federation team page whose ID a team season already uses without downloading anything', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))
        ->for(Season::factory()->state(['name' => '2025/2026']))
        ->create(['external_id' => 45019]);
    Season::factory()->current()->create(['name' => '2026/2027']);
    Http::preventStrayRequests();

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['federation_team_page_url' => 'Tato stránka už patří týmu FBC Kutná Hora B 2025/2026.']);
});

test('rejects a federation team page of another season than the one the team is added to', function () {
    Season::factory()->current()->create(['name' => '2027/2028']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['federation_team_page_url' => 'Stránka patří sezoně 2026/2027, tým ale přidáváte do sezony 2027/2028.']);
});

test('finds nothing to look a team up for while there is no season', function () {
    Http::preventStrayRequests();

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertNotFound();
});

test('rejects guests', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Http::preventStrayRequests();
    auth()->logout();

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertUnauthorized();
});

test('allows an administrator six lookups a minute', function () {
    // The limit counts from the first lookup, so a slow run must not let the clock move the window or the Retry-After.
    freezeTime();
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    $url = '/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']);

    for ($lookup = 1; $lookup <= 6; $lookup++) {
        $this->getJson($url)->assertOk();
    }

    $this->getJson($url)
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '60');
    Http::assertSentCount(6);

    $this->actingAs(User::factory()->create())->getJson($url)->assertOk();
});

test('answers that ceskyflorbal.cz is unavailable when the fixture list cannot be downloaded or read', function (Closure $fixtureListPage) {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake($fixtureListPage());
    Log::spy();

    $this->getJson('/teams/lookup?'.http_build_query(['federation_team_page_url' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019']))
        ->assertStatus(502)
        ->assertExactJson(['message' => 'Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně.']);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['external_id'] === 45019);
})->with([
    'blocked by bot protection' => fn (): Closure => fn (): mixed => Http::response('<!DOCTYPE html><html><head><title>Just a moment...</title></head><body></body></html>', 403),
    'a server error' => fn (): Closure => fn (): mixed => Http::response('', 500),
    'unreachable' => fn (): Closure => fn (): mixed => Http::failedConnection(),
    'a challenge page answered with 200' => fn (): Closure => fn (): mixed => Http::response('<!DOCTYPE html><html><head><title>Just a moment...</title></head><body><div id="challenge-running"></div></body></html>'),
]);
