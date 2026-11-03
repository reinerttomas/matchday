<?php

declare(strict_types=1);

use App\Enums\Category;
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

test('shows the team\'s category as a badge next to its name', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC VHST Kutná Hora', 'category' => Category::Women]))
        ->for(Season::factory()->current())
        ->create(['name' => 'FBC VHST Kutná Hora']);

    visit('/teams')
        ->assertSeeIn('tbody tr:first-child td:first-child', 'FBC VHST Kutná Hora')
        ->assertSeeIn('tbody tr:first-child td:first-child [data-slot="badge"]', 'ženy')
        ->assertNoJavaScriptErrors();
});

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

test('looks the team up from a pasted overview address, prefills its name and guessed category and adds it as a new team', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshotOf('FBC VHST Kutná Hora', '1. liga žen'));
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC VHST Kutná Hora')
        ->assertSeeIn('@federation-team-summary', '1. liga žen')
        ->assertSeeIn('@federation-team-summary', '2026/2027')
        ->assertAttribute('@federation-team-overview', 'href', 'https://www.ceskyflorbal.cz/team/detail/overview/45019')
        ->assertAttribute('@federation-team-overview', 'target', '_blank')
        ->assertValue('name', 'FBC VHST Kutná Hora')
        ->assertSeeIn('@new-team-category-select', 'ženy')
        ->assertSee('Kategorie odhadnutá ze soutěže „1. liga žen“.')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-vhst-kutna-hora-zeny'))
        ->click('@store-team')
        ->assertSee('Tým FBC VHST Kutná Hora je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->sole())
        ->season_id->toBe($season->id)
        ->external_id->toBe(45019)
        ->team->name->toBe('FBC VHST Kutná Hora')
        ->team->category->toBe(Category::Women)
        ->team->slug->toBe('fbc-vhst-kutna-hora-zeny');
});

test('previews the calendar address of a new team from its name and a category picked by hand', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSeeIn('@new-team-category-select', 'muži')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-kutna-hora-b-muzi'))
        ->click('@new-team-category-select')
        ->click('[role="option"]:has-text("junioři")')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-kutna-hora-b-juniori'))
        ->assertDontSee('Kategorie odhadnutá ze soutěže')
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertNoJavaScriptErrors();

    expect(Team::query()->sole())
        ->category->toBe(Category::Juniors)
        ->slug->toBe('fbc-kutna-hora-b-juniori');
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
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'kutna-hora-b-muzi'))
        ->click('@store-team')
        ->assertSee('Tým Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertNoJavaScriptErrors();

    expect(Team::query()->sole()->name)->toBe('Kutná Hora B');
});

test('preselects the carry-over team with the federation\'s team name and the guessed category and adds it to the season', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    $team = Team::factory()->create(['name' => 'Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for($previousSeason)->create(['name' => 'FBC  kutná hora B ']);
    $women = Team::factory()->create(['name' => 'Kutná Hora B', 'category' => Category::Women, 'slug' => 'kutna-hora-b-zeny']);
    TeamSeason::factory()->for($women)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSee('Pokračuje Kutná Hora B')
        ->assertSeeIn('@carry-over-team-category', 'muži')
        ->assertSee('Stejný název na svazu i kategorie.')
        ->assertAttribute('@carry-over-team-choice', 'aria-checked', 'true')
        ->assertSeeIn('@carry-over-team-select', 'Kutná Hora B')
        ->assertSeeIn('@carry-over-team-select', 'muži')
        ->click('@store-team')
        ->assertSee('je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect($team->teamSeasons()->whereBelongsTo($season)->sole()->external_id)->toBe(45019)
        ->and($women->teamSeasons()->whereBelongsTo($season)->exists())->toBeFalse()
        ->and(Team::query()->count())->toBe(2);
});

test('shows the carry-over team\'s own name and category instead of the new team\'s fields', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    $team = Team::factory()->create(['name' => 'Béčko Kutná Hora']);
    TeamSeason::factory()->for($team)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertAttribute('@carry-over-team-choice', 'aria-checked', 'true')
        ->assertSeeIn('@carry-over-team-select', 'Béčko Kutná Hora')
        ->assertSeeIn('@carry-over-team-select', 'muži')
        ->assertMissing('[name="name"]')
        ->assertMissing('@new-team-category-select')
        ->click('@new-team-choice')
        ->assertValue('[name="name"]', 'FBC Kutná Hora B')
        ->assertSeeIn('@new-team-category-select', 'muži')
        ->assertNoJavaScriptErrors();
});

test('offers the carry-over team of the category the administrator changes the new team to', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    $men = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($men)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    $women = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'category' => Category::Women, 'slug' => 'kutna-hora-b-zeny']);
    TeamSeason::factory()->for($women)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSeeIn('@carry-over-team-category', 'muži')
        ->click('@new-team-choice')
        ->click('@new-team-category-select')
        ->click('[role="option"]:has-text("ženy")')
        ->assertSeeIn('@carry-over-team-category', 'ženy')
        ->click('@carry-over-team-choice')
        ->assertSeeIn('@carry-over-team-select', 'ženy')
        ->click('@store-team')
        ->assertSee('je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect($women->teamSeasons()->whereBelongsTo($season)->sole()->external_id)->toBe(45019)
        ->and($men->teamSeasons()->whereBelongsTo($season)->exists())->toBeFalse();
});

test('keeps the carry-over team chosen by hand when the administrator changes the category', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    $previousSeason = Season::factory()->create(['name' => '2025/2026']);
    $renamed = Team::factory()->create(['name' => 'Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($renamed)->for($previousSeason)->create(['name' => 'Florbal Kutná Hora B']);
    $women = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'category' => Category::Women, 'slug' => 'kutna-hora-b-zeny']);
    TeamSeason::factory()->for($women)->for($previousSeason)->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertAttribute('@new-team-choice', 'aria-checked', 'true')
        ->click('@carry-over-team-choice')
        ->click('@carry-over-team-select')
        ->click('[role="option"]:has-text("Kutná Hora B"):has-text("muži")')
        ->click('@new-team-choice')
        ->click('@new-team-category-select')
        ->click('[role="option"]:has-text("ženy")')
        ->assertSeeIn('@carry-over-team-category', 'ženy')
        ->assertAttribute('@new-team-choice', 'aria-checked', 'true')
        ->click('@carry-over-team-choice')
        ->assertSeeIn('@carry-over-team-select', 'muži')
        ->assertNoJavaScriptErrors();
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
        ->click('@new-team-category-select')
        ->click('[role="option"]:has-text("dorostenci")')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-kutna-hora-b-dorostenci'))
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->sole())
        ->external_id->toBe(45019)
        ->team->name->toBe('FBC Kutná Hora B')
        ->team->category->toBe(Category::Youths);
});

test('forgets the lookup when the address is edited, so Enter does not add the team', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());
    Queue::fake([ImportFixtureList::class]);

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC Kutná Hora B')
        ->append('federation_team_page_url', '0')
        ->assertMissing('@federation-team-summary')
        ->assertMissing('@new-team-choice')
        ->assertButtonDisabled('@store-team')
        ->keys('federation_team_page_url', 'Enter')
        ->wait(1)
        ->assertVisible('@store-team')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->exists())->toBeFalse();
});

test('drops the carry-over team preselected by an earlier lookup when the next lookup names no carry-over team', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2025/2026']))->create(['name' => 'FBC Kutná Hora B']);
    Ceskyflorbal::fake(Http::sequence([
        Http::response(Ceskyflorbal::fixtureListSnapshot()),
        Http::response(Str::replace('FBC Kutná Hora B', 'FBC Sokol Kutná Hora B', Ceskyflorbal::fixtureListSnapshot())),
    ]));

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertAttribute('@carry-over-team-choice', 'aria-checked', 'true');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/matches/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC Sokol Kutná Hora B')
        ->assertSee('Tým z minulé sezony')
        ->assertAttribute('@new-team-choice', 'aria-checked', 'true')
        ->assertValue('name', 'FBC Sokol Kutná Hora B')
        ->click('@carry-over-team-choice')
        ->assertSeeIn('@carry-over-team-select', 'Vyberte tým')
        ->assertNoJavaScriptErrors();
});

test('keeps the carry-over team chosen by hand when a later lookup names no carry-over team', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2025/2026']))->create();
    Ceskyflorbal::fake(Ceskyflorbal::fixtureListSnapshot());

    $page = visit('/teams')->click('@add-team');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/overview/45019');

    $page->assertAttribute('@new-team-choice', 'aria-checked', 'true')
        ->click('@carry-over-team-choice')
        ->click('@carry-over-team-select')
        ->click('[role="option"]:has-text("Kutná Hora B")');
    pasteFederationTeamPageUrl($page, 'https://www.ceskyflorbal.cz/team/detail/matches/45019');

    $page->assertSeeIn('@federation-team-summary', 'FBC Kutná Hora B')
        ->assertAttribute('@carry-over-team-choice', 'aria-checked', 'true')
        ->assertSeeIn('@carry-over-team-select', 'Kutná Hora B')
        ->assertNoJavaScriptErrors();
});

test('does not look the team up when the dialog is cancelled from the address field', function () {
    Season::factory()->current()->create(['name' => '2026/2027']);
    Ceskyflorbal::fake(Http::response('', 500));

    visit('/teams')
        ->click('@add-team')
        ->type('federation_team_page_url', 'https://www.ceskyflorbal.cz/team/detail/overview/45019')
        ->click('Zrušit')
        ->assertMissing('@store-team')
        ->wait(1)
        ->assertDontSee('Český florbal teď neodpovídá.')
        ->assertNoJavaScriptErrors();
});

test('renames a team from its actions in the "Upravit tým" dialog with the name pre-filled', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->click('@edit-team')
        ->assertSee('Upravit tým FBC Kutná Hora B')
        ->assertValue('name', 'FBC Kutná Hora B')
        ->assertSeeIn('@team-category-select', 'muži')
        ->assertSee('Adresa kalendáře zůstává stejná')
        ->clear('name')
        ->type('name', 'FBC Sokol Kutná Hora B')
        ->click('@update-team')
        ->assertSee('Tým FBC Sokol Kutná Hora B je upravený.')
        ->assertMissing('@update-team')
        ->assertSeeIn('tbody tr:first-child td:first-child', 'FBC Sokol Kutná Hora B')
        ->assertNoJavaScriptErrors();

    expect($teamSeason->team->fresh())
        ->name->toBe('FBC Sokol Kutná Hora B')
        ->slug->toBe('kutna-hora-b');
});

test('changes a team\'s category from its actions and keeps its calendar address', function () {
    $teamSeason = teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->click('@edit-team')
        ->click('@team-category-select')
        ->click('[role="option"]:has-text("ženy")')
        ->click('@update-team')
        ->assertSee('Tým FBC Kutná Hora B je upravený.')
        ->assertMissing('@update-team')
        ->click('@team-season-actions')
        ->click('@edit-team')
        ->assertSeeIn('@team-category-select', 'ženy')
        ->assertNoJavaScriptErrors();

    expect($teamSeason->team->fresh())
        ->category->toBe(Category::Women)
        ->slug->toBe('kutna-hora-b');
});

test('keeps the "Upravit tým" dialog open with the error when the name is too long', function () {
    teamSeasonOnTeamsPageInBrowser();

    visit('/teams')
        ->click('@team-season-actions')
        ->click('@edit-team')
        ->fill('name', str_repeat('a', 101))
        ->click('@update-team')
        ->assertSee('Název týmu může mít nejvýše 100 znaků.')
        ->assertVisible('@update-team')
        ->assertNoJavaScriptErrors();
});
