<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * FBC Kutná Hora B, the only team season in the current season.
 */
function teamSeasonOnTeamsPageInBrowser(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/27']))
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
