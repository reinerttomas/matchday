<?php

declare(strict_types=1);

use App\Jobs\ImportFixtureList;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

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
        ->assertAttribute('@open-source', 'href', $teamSeason->source_url)
        ->assertAttribute('@open-source', 'target', '_blank')
        ->assertNoJavaScriptErrors();
});

test('previews the calendar address derived from a new team\'s name while it is typed, then adds the team', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    Queue::fake([ImportFixtureList::class]);

    visit('/teams')
        ->click('@add-team')
        ->assertSee('Adresa kalendáře později nepůjde změnit')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'nazev-tymu'))
        ->type('name', 'FBC Kutná Hora B')
        ->assertSeeIn('@calendar-url-preview', route('calendar', 'fbc-kutna-hora-b'))
        ->fill('source_url', 'https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertVisible('@auto-import-switch')
        ->assertNoJavaScriptErrors();

    expect(TeamSeason::query()->sole())
        ->season_id->toBe($season->id)
        ->team->name->toBe('FBC Kutná Hora B')
        ->team->slug->toBe('fbc-kutna-hora-b');
});

test('carries a team over from a previous season chosen in the dialog', function () {
    $season = Season::factory()->current()->create(['name' => '2026/2027']);
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']);
    TeamSeason::factory()->for($team)->for(Season::factory()->state(['name' => '2025/2026']))->create(['name' => 'FBC Kutná Hora B']);
    Queue::fake([ImportFixtureList::class]);

    visit('/teams')
        ->click('@add-team')
        ->click('@carry-over-team')
        ->click('@carry-over-team-select')
        ->assertSeeIn('[role="listbox"]', 'FBC Kutná Hora B')
        ->click('[role="option"]')
        ->fill('source_url', 'https://www.ceskyflorbal.cz/team/detail/matches/45019')
        ->click('@store-team')
        ->assertSee('Tým FBC Kutná Hora B je přidaný do sezony 2026/2027.')
        ->assertMissing('@store-team')
        ->assertNoJavaScriptErrors();

    expect($team->teamSeasons()->whereBelongsTo($season)->sole()->external_id)->toBe(45019);
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
