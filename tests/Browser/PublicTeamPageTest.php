<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;

test('reveals the rest of the season beyond the first four match days', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();
    foreach (range(1, 6) as $week) {
        Fixture::factory()->for($teamSeason)->create(['date' => today('Europe/Prague')->addWeeks($week)->toDateString()]);
    }

    $page = visit('/t/kutna-hora-b');

    $page->assertCount('@match-day', 4)
        ->click('Zobrazit celou sezonu')
        ->assertCount('@match-day', 6)
        ->assertDontSee('Zobrazit celou sezonu')
        ->assertNoJavaScriptErrors();
});

test('fits a phone screen without horizontal scrolling', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create(['name' => 'FBC Kutná Hora B', 'competition_name' => '2. liga mužů, skupina 3']);
    Fixture::factory()->for($teamSeason)->rescheduled()->postponed()->create([
        'opponent_name' => 'Florbalový klub Tatran Střešovice Praha – juniorský výběr C',
        'date' => today('Europe/Prague')->addDay()->toDateString(),
    ]);
    Fixture::factory()->for($teamSeason)->tbdTime()->create(['date' => today('Europe/Prague')->addDay()->toDateString()]);

    $page = visit('/t/kutna-hora-b')->on()->mobile();

    $page->assertSee('FBC Kutná Hora B')
        ->assertSee('Odloženo')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
});

test('puts the subscribe option and instructions for the player\'s device first', function (string $device, string $userAgent, string $buttons, string $instructionsTab) {
    TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->{$device}()->withUserAgent($userAgent);

    $page->assertSee('Přidat zápasy do kalendáře')
        ->assertScript('Array.from(document.querySelectorAll("[data-test=subscribe-button]")).map((link) => link.textContent).join(" | ")', $buttons)
        ->assertScript('Array.from(document.links).every((link) => ! link.hasAttribute("download") && ! (link.protocol.startsWith("http") && link.pathname.endsWith(".ics")))')
        ->assertAttribute("@instructions-tab-{$instructionsTab}", 'aria-selected', 'true')
        ->assertNoJavaScriptErrors();
})->with([
    'iPhone' => ['iPhone15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'iPhone / Mac | Google Kalendář | Outlook', 'iphone'],
    'Mac' => ['desktop', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15', 'iPhone / Mac | Google Kalendář | Outlook', 'iphone'],
    'Android' => ['pixel8', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36', 'Google Kalendář | iPhone / Mac | Outlook', 'android'],
    'Windows desktop' => ['desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36', 'Google Kalendář | iPhone / Mac | Outlook', 'google'],
]);

test('switches the subscribe instructions between devices', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b');

    $page->click('@instructions-tab-google')
        ->assertSeeIn('@instructions', 'Z adresy URL')
        ->click('@instructions-tab-android')
        ->assertSeeIn('@instructions', 'Verze pro počítač')
        ->assertDontSeeIn('@instructions', 'Z adresy URL')
        ->click('@instructions-tab-iphone')
        ->assertSeeIn('@instructions', 'Přidat odebíraný kalendář')
        ->assertDontSeeIn('@instructions', 'Verze pro počítač')
        ->click('@instructions-tab-outlook')
        ->assertSeeIn('@instructions', 'Přihlásit se k odběru z webu')
        ->assertDontSeeIn('@instructions', 'Přidat odebíraný kalendář')
        ->assertAttribute('@instructions-tab-outlook', 'aria-selected', 'true')
        ->assertAttribute('@instructions-tab-google', 'aria-selected', 'false')
        ->assertNoJavaScriptErrors();
});

test('copies the calendar address', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b');
    // Headless Chromium denies clipboard access, so the copied text is captured where it leaves the page.
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async (text) => { window.copiedText = text; } })');

    $page->assertDontSee('Zkopírováno')
        ->click('@copy-address')
        ->assertSeeIn('@copy-address', 'Zkopírováno')
        ->assertScript('document.querySelector("[data-test=copy-status]").textContent', 'Zkopírováno')
        ->assertScript('window.copiedText', $page->value('@calendar-address'))
        ->assertScript('window.copiedText.endsWith("/calendar/kutna-hora-b.ics")')
        ->assertNoJavaScriptErrors();
});

test('shows a qr code of the page on a wide screen only', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    visit('/t/kutna-hora-b')->on()->desktop()
        ->assertSee('Naskenujte kód')
        ->assertVisible('@qr-code')
        ->assertNoJavaScriptErrors();

    visit('/t/kutna-hora-b')->on()->mobile()
        ->assertSee('Přidat zápasy do kalendáře')
        ->assertMissing('@qr-code')
        ->assertNoJavaScriptErrors();
});
