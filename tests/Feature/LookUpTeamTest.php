<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Support\Ceskyflorbal;

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
            'season' => '2026/2027',
            'overviewUrl' => 'https://www.ceskyflorbal.cz/team/detail/overview/45019',
        ]);
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
