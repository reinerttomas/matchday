<?php

declare(strict_types=1);

use App\Jobs\ImportFixtureList;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Pest\Browser\Api\AwaitableWebpage;
use Tests\Support\Ceskyflorbal;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Paste the address into the "Přidat tým" dialog; headless Chromium denies the clipboard, so the paste is dispatched as the input event it raises.
 */
function pasteFederationTeamPageUrl(AwaitableWebpage $page, string $address): void
{
    $page->script(sprintf(<<<'JS'
        (() => {
        const input = document.querySelector('[name="federation_team_page_url"]');
        input.focus();
        Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(input, %s);
        input.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertFromPaste' }));
        })()
        JS, json_encode($address)));
}

/**
 * FBC Kutná Hora B, the only team season in the current season.
 */
function teamSeasonOnTeamsPageInBrowser(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/2027']))
        ->create(['name' => 'FBC Kutná Hora B']);
}

test('switches off a team season\'s auto import from its switch', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->assertAttribute('@auto-import-switch', 'aria-checked', 'true')
        ->click('@auto-import-switch')
        ->assertSee('Automatický import týmu FBC Kutná Hora B je vypnutý.')
        ->assertAttribute('@auto-import-switch', 'aria-checked', 'false')
        ->assertNoJavaScriptErrors();

    expect($teamSeason->fresh()->auto_import_enabled)->toBeFalse();
});

test('copies the calendar address from the team actions', function () {
    teamSeasonOnTeamsPageInBrowser();

    $page = visit('/teams');
    // Headless Chromium denies clipboard access, so the copied text is captured where it leaves the page.
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async (text) => { window.copiedText = text; } })');

    $page->click('@team-season-actions')
        ->click('Kopírovat adresu kalendáře')
        ->assertSee('Adresa kalendáře je zkopírovaná.')
        ->assertScript('window.copiedText', route('calendar', 'kutna-hora-b'))
        ->assertNoJavaScriptErrors();
});

test('shows the calendar address to copy by hand when the browser blocks the clipboard', function () {
    teamSeasonOnTeamsPageInBrowser();

    $page = visit('/teams');
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async () => { throw new Error("Denied"); } })');

    $page->click('@team-season-actions')
        ->click('Kopírovat adresu kalendáře')
        ->assertSee('Prohlížeč nedovolil adresu zkopírovat.')
        ->assertValue('@calendar-address', route('calendar', 'kutna-hora-b'))
        ->assertScript('window.getSelection().toString() || document.activeElement.value.substring(document.activeElement.selectionStart, document.activeElement.selectionEnd)', route('calendar', 'kutna-hora-b'))
        ->assertNoJavaScriptErrors();
});

test('links the team actions to the public page and the fixture list on ceskyflorbal.cz in new tabs', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->assertAttribute('@open-public-page', 'href', route('public-team-page', 'kutna-hora-b'))
        ->assertAttribute('@open-public-page', 'target', '_blank')
        ->assertAttribute('@open-source', 'href', "https://www.ceskyflorbal.cz/team/detail/matches/{$teamSeason->external_id}")
        ->assertAttribute('@open-source', 'target', '_blank')
        ->assertNoJavaScriptErrors();
});

test('links the team actions to the team\'s overview on ceskyflorbal.cz in a new tab', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->assertSee('Přehled na Český florbal')
        ->assertAttribute('@open-overview', 'href', "https://www.ceskyflorbal.cz/team/detail/overview/{$teamSeason->external_id}")
        ->assertAttribute('@open-overview', 'target', '_blank')
        ->assertNoJavaScriptErrors();
});

test('looks the team up from a pasted overview address, prefills its name and adds it as a new team', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC Kutná Hora B')
        ->assertSeeIn('@federation-team-summary', 'PH a SČ liga mužů')
        ->assertSeeIn('@federation-team-summary', '2026/2027')
        ->assertAttribute('@federation-team-overview', 'href', 'https://www.ceskyflorbal.cz/team/detail/overview/45019')
        ->assertAttribute('@federation-team-overview', 'target', '_blank')
        ->assertValue('name', 'FBC Kutná Hora B')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-kutna-hora-b'))
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->sole())
        ->season_id->toBe($season->id)
        ->external_id->toBe(45019)
        ->team->name->toBe('FBC Kutná Hora B')
        ->team->slug->toBe('fbc-kutna-hora-b');
});

test('keeps a name typed before the lookup', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Http::sequence([
        Http::response(Ceskyflorbal::fixtureListSnapshot()),
        Http::response(Str::replace('FBC Kutná Hora B', 'FBC Sokol Kutná Hora B', Ceskyflorbal::fixtureListSnapshot())),
    ]));
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertValue('name', 'FBC Kutná Hora B')
        ->clear('name')
        ->type('name', 'Kutná Hora B');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/matches/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC Sokol Kutná Hora B')
        ->assertValue('name', 'Kutná Hora B')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'kutna-hora-b'))
        ->click('@store-team')
        ->assertSee('Tým Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertNoJavaScriptErrors();

    expect(Team::query()->sole()->name)->toBe('Kutná Hora B');
});

test('preselects the carry-over team with the federation\'s team name and adds it to the season', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'FBC  kutná hora B ', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2025/2026']))->create();
    Team::factory()->create(['name' => 'FBC Kutná Hora', 'slug' => 'kutna-hora']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSee('Pokračuje FBC kutná hora B')
        ->assertSee('Stejný název jako tým z minulé sezony')
        ->assertAttribute('@carry-over-team-choice', 'aria-checked', 'true')
        ->assertSeeIn('@carry-over-team-select', 'FBC kutná hora B')
        ->click('@store-team')
        ->assertSee('je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect($team->teamSeasons()->whereBelongsTo($season)->sole()->external_id)->toBe(45019)
        ->and($team->fresh()->name)->toBe('FBC  kutná hora B ')
        ->and(Team::query()->count())->toBe(2);
});

test('shows on the address field that the federation team ID is already used, without the choice of team', function () {
    Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSee('Tato stránka už patří týmu')
        ->assertAttribute('federation_team_page_url', 'aria-invalid', 'true')
        ->assertMissing('@federation-team-summary')
        ->assertMissing('@new-team-choice')
        ->assertButtonDisabled('@store-team')
        ->assertNoJavaScriptErrors();
});

test('warns when ceskyflorbal.cz does not answer and still adds a team filled in by hand', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Http::response('', 500));
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSee('Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně.')
        ->assertMissing('@federation-team-summary')
        ->assertAttribute('@new-team-choice', 'aria-checked', 'true')
        ->type('name', 'FBC Kutná Hora B')
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->sole())
        ->external_id->toBe(45019)
        ->team->name->toBe('FBC Kutná Hora B');
});

test('renames a team from its actions in a dialog with the name pre-filled', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->click('@rename-team')
        ->assertValue('name', 'FBC Kutná Hora B')
        ->assertSee('Adresa kalendáře zůstává stejná')
        ->clear('name')
        ->type('name', 'FBC Sokol Kutná Hora B')
        ->click('@update-team-name')
        ->assertSee('Tým je přejmenovaný na FBC Sokol Kutná Hora B.')
        ->assertMissing('@update-team-name')
        ->assertSeeIn('tbody tr:first-child td:first-child', 'FBC Sokol Kutná Hora B')
        ->assertNoJavaScriptErrors();

    expect($teamSeason->team->fresh())
        ->name->toBe('FBC Sokol Kutná Hora B')
        ->slug->toBe('kutna-hora-b');
});

test('keeps the rename dialog open with the error when the name is too long', function () {
    teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->click('@rename-team')
        ->fill('name', str_repeat('a', 101))
        ->click('@update-team-name')
        ->assertSee('Název týmu může mít nejvýše 100 znaků.')
        ->assertVisible('@update-team-name')
        ->assertNoJavaScriptErrors();
});
