# 46 — One clear way to subscribe per device

**What to build:** The subscription card offers three equal buttons, two copy controls and a help dialog, and the in-app browser notice from ticket 43 adds more on top. Each fix has added choices. Make the page lead with the one action that works for the player's situation:

- **In a real browser** (most players, because WhatsApp on iPhone opens links straight in Safari), one primary button for the player's device, plus the second step Android needs. Everything else is folded away under "Jiný kalendář".
- **In an in-app browser** (Messenger, Instagram, …), no subscribe buttons at all, because they don't work there. The player only sees how to open the page in their browser.

**Blocked by:** None (can start immediately)

**Status:** resolved

### In a real browser

- [x] The card shows one primary button chosen by `useDevice()`:
    - `apple`: **"Přidat do kalendáře v iPhonu"** → `calendar.webcal`, recorded as `webcal`
    - `android` and `other`: **"Přidat do Google Kalendáře"** → `calendar.google` in a new tab, recorded as `google`
- [x] On `android`, a short second step sits directly under the primary button, not hidden in the dialog: "Pak v aplikaci Kalendář Google otevřete Nastavení, vyberte kalendář týmu a zapněte Synchronizace." Google says a subscribed calendar can't be added in its phone app. Without sync turned on, the calendar stays on the web and never shows up in the phone.
- [x] Under the primary button, a "Jiný kalendář" toggle (shadcn `Collapsible`, collapsed by default) reveals the other two subscribe buttons (Google / iPhone / Outlook minus the primary one) and "Kopírovat adresu". Opening it records a new `TeamPageAction::OtherOptionsOpen` (`other_options_open`).
- [x] "Nefunguje to?" and its dialog (per-device instructions, the address to copy) stay as they are.
- [x] The existing actions (`google`, `webcal`, `outlook`, `copy_address`, `help_open`) keep being recorded the same way, wherever their button now sits.

### In an in-app browser

- [x] When `useIsInAppBrowser()` is true, the subscription card is not rendered. The notice from ticket 43 takes its place.
- [x] The notice keeps its title "Otevřete stránku v prohlížeči". Its text says that the calendar can be added only from the browser (Safari / Chrome). Below that it shows the manual steps as a numbered list:
    1. "Klepněte na ⋯ v rohu obrazovky."
    2. "Zvolte „Otevřít v prohlížeči“."
    3. "Přidejte si kalendář jedním tlačítkem."
- [x] On `android` the notice also offers the "Otevřít v prohlížeči" button (`intent://`, `escape_intent`) above the steps. Sources report it as reliable on Android, but it is untested on a device.
- [x] The `x-safari-https://` button is removed, along with the `escape_safari` action (`TeamPageAction::EscapeSafari` and the TS union member). This branch isn't released yet, so the only `escape_safari` rows are local test data.
- [x] "Kopírovat odkaz" (`copy_page_link`) stays, so the player can paste the page into Safari / Chrome themselves.
- [x] On `other` devices the notice shows the same steps and "Kopírovat odkaz", with no escape button.

### Tests

- [x] Browser tests in `tests/Browser/PublicTeamPageTest.php` cover:
    - iPhone Safari: one primary "Přidat do kalendáře v iPhonu" with the `webcal://` href. The other buttons are not visible until "Jiný kalendář" is opened, and opening it records `other_options_open`.
    - Android Chrome: primary "Přidat do Google Kalendáře" and the sync step visible.
    - Desktop: primary "Přidat do Google Kalendáře" with no Android sync step.
    - Messenger on iPhone (the real UA from Notes): no `@subscribe-button` on the page, the numbered steps, no `x-safari` link anywhere, and "Kopírovat odkaz".
    - Messenger on Android: the `intent://` button and the steps.
    - no horizontal scroll and no JavaScript errors on mobile, both with and without the notice.
- [x] Update or remove the ticket-43/44 browser tests that assume three equal buttons or the Safari escape button. The feature dataset for `TeamPageAction` follows the enum change.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-09. It replaces an earlier draft of this ticket, which special-cased Messenger on iPhone ("Funguje až v Safari" etc.). The user chose one clear path per situation over more exceptions.
- Players are on iPhone and Android, and the link is shared in the team's WhatsApp group.
- The common pattern elsewhere (TeamSnap, SportsEngine, university calendars, add-to-calendar-button) uses the same building blocks: `webcal` for Apple, Google's `cid` link, a copy-URL fallback, and an instruction instead of buttons in webviews. What they add is focus.
    - Google: "You can't subscribe to a calendar in the Google Calendar app for Android, iPhone, or iPad", https://support.google.com/calendar/answer/37100
    - TeamSnap's Android steps end with turning Sync on for the subscribed calendar: https://helpme.teamsnap.com/article/128-subscribe-to-a-team-schedule
    - add-to-calendar-button shows an instruction instead of the link in iPhone webviews: https://add-to-calendar-button.com/configuration
- Real-device test on 2026-10-09, through a Cloudflare quick tunnel to the local app (local `team_page_events` 3–22):
    - **WhatsApp on iPhone (iOS 27):** opens links straight in Safari (UA `… Version/27.0 Mobile/15E148 Safari/604.1`, not flagged as in-app). `webcal` triggers the iOS subscribe flow. Google goes through the Google Calendar app to its web page in Safari, which shows the subscribe confirmation.
    - **Messenger on iPhone (iPhone 13 mini, iOS 27, Messenger 582):** flagged as `messenger`. `x-safari-https://` was tapped 6× and did nothing. `webcal://` was tapped 2× with no dialog and no feed fetch. Google works through the same detour. The clipboard works. The ⋯ menu item is called „Otevřít v prohlížeči“. UA: `Mozilla/5.0 (iPhone; CPU iPhone OS 27_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/24A437 [FBAN/FBIOS;FBAV/582.0.0.26.106;FBBV/1084662339;FBDV/iPhone14,4;FBMD/iPhone;FBSN/iOS;FBSV/27.0;FBSS/3;FBCR/;FBID/phone;FBLC/cs_CZ;FBOP/80]`
    - **Android:** not tested. The user has no Android device.
- iOS reporting the calendar as already subscribed: the user had subscribed to it before, so no new fetch was expected. Which UA Apple fetches the feed with is still unknown; check it after release in `calendar_fetches`.

## Comments

- 2026-10-09: Implemented in 79b5978. Decisions made during implementation:
    - The buttons folded under "Jiný kalendář" keep their short labels („Google Kalendář“, „iPhone / Mac“, „Outlook“), in that order.
    - `other_options_open` is recorded every time the fold opens, the same way `help_open` is.
    - The help dialog's steps were reworded to the new button labels.
    - Open for the user: Mac and iPad also show „Přidat do kalendáře v iPhonu“, because `useDevice()` counts them as `apple`. The options are to keep it, to use „Přidat do Apple Kalendáře“ for all Apple devices, or to keep "v iPhonu" only on iPhone.
