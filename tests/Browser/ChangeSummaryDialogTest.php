<?php

declare(strict_types=1);

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * FBC Kutná Hora B, the only team season in the current season, already through its initial import.
 */
function teamSeasonOnChangesPage(): TeamSeason
{
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/2027']))
        ->create(['name' => 'FBC Kutná Hora B']);
    Import::factory()->for($teamSeason)->create(['started_at' => now()->subWeek()]);

    return $teamSeason;
}

test('sends the edited change summary to WhatsApp and marks it as sent', function () {
    $teamSeason = teamSeasonOnChangesPage();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => now()->subDay()]);
    $fixture = Fixture::factory()->for($teamSeason)->home()->create(['date' => '2026-10-11', 'opponent_name' => 'Las Plantas']);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::Time, null, '09:00')->create();
    $editedText = "📅 Změny v rozpisu FBC Kutná Hora B\n• NE 11. 10. FBC Kutná Hora B – Las Plantas: čas doplněn 9:00, sraz v 8:15 & bez dresů";

    $page = visit('/changes');
    // Headless Chromium denies clipboard access, so the copied text is captured where it leaves the page.
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async (text) => { window.copiedText = text; } })');

    $page->assertSeeIn('[data-sidebar=menu-badge]', '1')
        ->click('@send-change-summary')
        ->assertSee('Souhrn změn pro WhatsApp')
        ->assertValue('@change-summary-text', implode("\n", [
            '📅 Změny v rozpisu FBC Kutná Hora B',
            '• NE 11. 10. FBC Kutná Hora B – Las Plantas: čas doplněn 9:00',
            '',
            'Kalendář: '.route('public-team-page', 'kutna-hora-b'),
        ]))
        ->fill('@change-summary-text', $editedText)
        ->click('@copy-change-summary')
        ->assertScript('window.copiedText', $editedText)
        ->assertAttribute('@open-whatsapp', 'href', 'https://wa.me/?text='.rawurlencode($editedText))
        ->assertAttribute('@open-whatsapp', 'target', '_blank')
        ->assertMissing('@mark-as-sent');

    // The link opens wa.me in a new tab, which the test doesn't need, so only the page's own click handling runs.
    $page->script('document.querySelector("[data-test=open-whatsapp]").addEventListener("click", (event) => event.preventDefault())');

    $page->click('@open-whatsapp')
        ->click('@mark-as-sent')
        ->assertSee('Souhrn změn je označený jako odeslaný.')
        ->assertDontSee('Souhrn změn pro WhatsApp')
        ->assertSee('Odesláno týmu')
        ->assertMissing('@send-change-summary')
        ->assertMissing('[data-sidebar=menu-badge]')
        ->assertNoJavaScriptErrors();

    expect($import->fresh()->notified_at)->not->toBeNull();
});

test('offers only marking as sent when none of the import\'s revisions is worth telling the team', function () {
    $teamSeason = teamSeasonOnChangesPage();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => now()->subDay()]);
    $fixture = Fixture::factory()->for($teamSeason)->create(['is_rescheduled' => false]);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::IsRescheduled, '1', '0')->create();

    $page = visit('/changes');

    $page->click('@send-change-summary')
        ->assertSee('není co poslat')
        ->assertMissing('@change-summary-text')
        ->assertMissing('@open-whatsapp')
        ->click('@mark-as-sent')
        ->assertSee('Odesláno týmu')
        ->assertMissing('[data-sidebar=menu-badge]')
        ->assertNoJavaScriptErrors();

    expect($import->fresh()->notified_at)->not->toBeNull();
});
