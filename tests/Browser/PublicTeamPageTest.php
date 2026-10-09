<?php

declare(strict_types=1);

use App\Enums\TeamPageAction;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamPageEvent;
use App\Models\TeamSeason;

const MESSENGER_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/22A3354 [FBAN/MessengerForiOS;FBAV/478.0.0.40.109;FBBV/650000000;FBDV/iPhone15,2;FBMD/iPhone;FBSN/iOS;FBSV/18.0;FBSS/3;FBCR/;FBID/phone;FBLC/cs_CZ;FBOP/5]';

const MESSENGER_ANDROID = 'Mozilla/5.0 (Linux; Android 14; Pixel 8 Build/AP2A.240805.005; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/129.0.6668.100 Mobile Safari/537.36 [FB_IAB/Orca-Android;FBAV/478.0.0.43.115;]';

/**
 * Waits until the app has answered the given number of beacons; a beacon shows up in resource timing only then.
 */
function answeredBeacons(int $count): string
{
    return <<<JS
        function () {
            return new Promise((resolve) => {
                const startedAt = Date.now();
                const countAnsweredBeacons = () => {
                    const answered = performance.getEntriesByType("resource").filter((entry) => entry.initiatorType === "beacon").length;
                    if (answered >= {$count} || Date.now() - startedAt > 5000) {
                        resolve(answered);
                    } else {
                        setTimeout(countAnsweredBeacons, 50);
                    }
                };
                countAnsweredBeacons();
            });
        }
        JS;
}

test('reveals the rest of the season beyond the first four match days', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
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

test('fits a phone screen without horizontal scrolling, with the subscribe card above the schedule', function () {
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
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
        ->assertSeeIn('@relative-day', 'Zítra')
        ->assertScript('document.getElementById("calendar-subscription").getBoundingClientRect().bottom < document.getElementById("schedule").getBoundingClientRect().top')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
});

test('puts the subscribe option and instructions for the player\'s device first', function (string $device, string $userAgent, string $buttons, string $instructionsTab) {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->{$device}()->withUserAgent($userAgent);

    $page->assertSee('Zápasy do kalendáře')
        ->assertScript('Array.from(document.querySelectorAll("[data-test=subscribe-button]")).map((link) => link.textContent).join(" | ")', $buttons)
        ->assertScript('Array.from(document.links).every((link) => ! link.hasAttribute("download") && ! (link.protocol.startsWith("http") && link.pathname.endsWith(".ics")))')
        ->click('Nefunguje to?')
        ->assertVisible('@help-dialog')
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
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b');

    $page->click('Nefunguje to?')
        ->click('@instructions-tab-google')
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
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b');
    // Headless Chromium denies clipboard access, so the copied text is captured where it leaves the page.
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async (text) => { window.copiedText = text; } })');

    $page->assertDontSee('Adresa zkopírována')
        ->click('@copy-address')
        ->assertSeeIn('@copy-address', 'Adresa zkopírována')
        ->assertScript('document.querySelector("[data-test=copy-status]").textContent', 'Adresa zkopírována')
        ->assertScript('window.copiedText.endsWith("/calendar/kutna-hora-b.ics")')
        ->assertMissing('@help-dialog')
        ->assertNoJavaScriptErrors();
});

test('opens the manual instructions with the address to copy by hand when the clipboard is blocked', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b');
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async () => { throw new Error("Blocked"); } })');

    $page->click('@copy-address')
        ->assertVisible('@help-dialog')
        ->assertDontSee('Adresa zkopírována')
        ->assertScript('document.querySelector("[data-test=calendar-address]").value.endsWith("/calendar/kutna-hora-b.ics")');
});

test('records the page view and a tap on a subscribe button on a phone', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->mobile()->assertSee('Zápasy do kalendáře');
    // Keeps the test off calendar.google.com; the page's own click handler runs after this one.
    $page->script('window.addEventListener("click", (event) => event.preventDefault(), { capture: true })');

    $page->click('Google Kalendář')
        ->assertScript(answeredBeacons(2), 2)
        ->assertNoJavaScriptErrors();

    expect(TeamPageEvent::query()->orderBy('id')->pluck('action')->all())->toBe([TeamPageAction::PageView, TeamPageAction::Google]);
});

test('offers to open the page in the default browser from Messenger on Android, and records the tap', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b?utm_source=messenger')->on()->mobile()->withUserAgent(MESSENGER_ANDROID);
    // Keeps the test inside the page; the page's own click handler runs after this one.
    $page->script('window.addEventListener("click", (event) => event.preventDefault(), { capture: true })');

    $page->assertSeeIn('@in-app-browser-notice', 'Otevřete stránku v prohlížeči')
        ->assertSeeIn('@escape-in-app-browser', 'Otevřít v prohlížeči')
        ->assertScript('document.querySelector("[data-test=escape-in-app-browser]").getAttribute("href") === `intent://${location.host}/t/kutna-hora-b?utm_source=messenger#Intent;scheme=${location.protocol.replace(":", "")};end`')
        ->assertScript('document.querySelector("[data-test=in-app-browser-notice]").getBoundingClientRect().bottom < document.getElementById("calendar-subscription").getBoundingClientRect().top')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('@escape-in-app-browser')
        ->assertScript(answeredBeacons(2), 2)
        ->assertNoJavaScriptErrors();

    expect(TeamPageEvent::query()->orderBy('id')->get(['action', 'in_app_browser'])->toArray())->toBe([
        ['action' => 'page_view', 'in_app_browser' => 'messenger'],
        ['action' => 'escape_intent', 'in_app_browser' => 'messenger'],
    ]);
});

test('offers to open the page in Safari from Messenger on iPhone, with the manual path, and records the tap', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->iPhone15()->withUserAgent(MESSENGER_IPHONE);
    $page->script('window.addEventListener("click", (event) => event.preventDefault(), { capture: true })');

    $page->assertSeeIn('@in-app-browser-notice', 'Otevřete stránku v prohlížeči')
        ->assertSeeIn('@escape-in-app-browser', 'Otevřít v Safari')
        ->assertScript('document.querySelector("[data-test=escape-in-app-browser]").getAttribute("href") === `x-safari-${location.href}`')
        ->assertSeeIn('@in-app-browser-notice', 'Pokud se nic nestane, klepněte na ⋯ a zvolte „Otevřít v prohlížeči“.')
        ->assertCount('@subscribe-button', 3)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('@escape-in-app-browser')
        ->assertScript(answeredBeacons(2), 2)
        ->assertNoJavaScriptErrors();

    expect(TeamPageEvent::query()->orderBy('id')->get(['action', 'in_app_browser'])->toArray())->toBe([
        ['action' => 'page_view', 'in_app_browser' => 'messenger'],
        ['action' => 'escape_safari', 'in_app_browser' => 'messenger'],
    ]);
});

test('copies the page link from an in-app browser, and records the tap', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->mobile()->withUserAgent(MESSENGER_ANDROID);
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async (text) => { window.copiedText = text; } })');

    $page->click('@copy-page-link')
        ->assertSeeIn('@copy-page-link', 'Odkaz zkopírován')
        ->assertScript('document.querySelector("[data-test=copy-page-link-status]").textContent', 'Odkaz zkopírován')
        ->assertScript('window.copiedText === location.href')
        ->assertMissing('@page-link')
        ->assertScript(answeredBeacons(2), 2)
        ->assertNoJavaScriptErrors();

    expect(TeamPageEvent::query()->orderBy('id')->get(['action', 'in_app_browser'])->toArray())->toBe([
        ['action' => 'page_view', 'in_app_browser' => 'messenger'],
        ['action' => 'copy_page_link', 'in_app_browser' => 'messenger'],
    ]);
});

test('shows the page link to copy by hand when the in-app browser blocks the clipboard', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->iPhone15()->withUserAgent(MESSENGER_IPHONE);
    $page->script('Object.defineProperty(navigator.clipboard, "writeText", { value: async () => { throw new Error("Blocked"); } })');

    $page->assertMissing('@page-link')
        ->click('@copy-page-link')
        ->assertVisible('@page-link')
        ->assertDontSee('Odkaz zkopírován')
        ->assertScript('document.querySelector("[data-test=page-link]").value === location.href')
        ->assertScript('document.activeElement === document.querySelector("[data-test=page-link]")')
        ->assertNoJavaScriptErrors();
});

test('asks to leave Instagram on Android too', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->mobile()->withUserAgent('Mozilla/5.0 (Linux; Android 14; Pixel 8 Build/AP2A.240805.005; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/129.0.6668.100 Mobile Safari/537.36 Instagram 350.0.0.42.83 Android (34/14; 420dpi; 1080x2400; Google/google; Pixel 8; shiba; shiba; cs_CZ; 640000000)');

    $page->assertSeeIn('@in-app-browser-notice', 'Otevřete stránku v prohlížeči')
        ->assertSeeIn('@escape-in-app-browser', 'Otevřít v prohlížeči')
        ->assertNoJavaScriptErrors();
});

test('offers only the manual path out of an in-app browser on a device that is neither Android nor Apple', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->desktop()->withUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 NetType/WIFI MicroMessenger/7.0.20.1781(0x6700143B) WindowsWechat(0x63090a13) XWEB/9129 Flue');

    $page->assertSeeIn('@in-app-browser-notice', 'Otevřete stránku v prohlížeči přes nabídku aplikace.')
        ->assertMissing('@escape-in-app-browser')
        ->assertDontSeeIn('@in-app-browser-notice', 'Pokud se nic nestane')
        ->assertSeeIn('@copy-page-link', 'Kopírovat odkaz')
        ->assertNoJavaScriptErrors();
});

test('shows no in-app browser notice in a regular browser', function (string $device, string $userAgent) {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->{$device}()->withUserAgent($userAgent);

    $page->assertSee('Zápasy do kalendáře')
        ->assertMissing('@in-app-browser-notice')
        ->assertNoJavaScriptErrors();
})->with([
    'Safari on iPhone' => ['iPhone15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'],
    'Chrome on iPhone' => ['iPhone15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/129.0.6668.69 Mobile/15E148 Safari/604.1'],
    'Firefox on iPhone' => ['iPhone15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/131.0 Mobile/15E148 Safari/605.1.15'],
    'Chrome on Android' => ['pixel8', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36'],
    'Firefox on Android' => ['pixel8', 'Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0'],
    'Chrome on Windows' => ['desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36'],
]);

test('asks to leave an in-app browser it cannot name, and records it as a webview', function () {
    TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current())
        ->create();

    $page = visit('/t/kutna-hora-b')->on()->mobile()->withUserAgent('Mozilla/5.0 (Linux; Android 14; Pixel 8 Build/AP2A.240805.005; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/129.0.6668.100 Mobile Safari/537.36');

    $page->assertSeeIn('@in-app-browser-notice', 'Otevřete stránku v prohlížeči')
        ->assertScript(answeredBeacons(1), 1)
        ->assertNoJavaScriptErrors();

    expect(TeamPageEvent::query()->sole())
        ->action->toBe(TeamPageAction::PageView)
        ->in_app_browser->toBe('webview');
});
