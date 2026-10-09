# 43 — Offer to open the public team page outside an in-app browser

**What to build:** Players mostly open `/t/{slug}` from a link in Messenger or WhatsApp, which loads it in the app's own in-app browser. Subscribing from there is unreliable. Google blocks signing in from embedded browsers (`403 disallowed_useragent`), so "Google Kalendář" bounces through the Calendar app to the default browser, and an in-app browser often can't hand a `webcal://` link to the Calendar app. When the page detects an in-app browser, it shows a notice above the "Zápasy do kalendáře" card asking the player to open the page in their real browser. One tap opens the page there, with a manual fallback when that tap does nothing. Outside an in-app browser the page stays exactly as it is.

**Blocked by:** 44 — Record calendar subscription activity

**Status:** ready-for-agent

- [ ] `inapp-spy` (approved) is a pnpm dependency. Detection calls `InAppSpy()` on the client, like `detectDevice()` in `calendar-subscription.tsx`. The server renders no notice, and the client shows it after hydration (`useSyncExternalStore` with a server snapshot of "not in-app"). The notice shows whenever `isInApp` is true, for every app `inapp-spy` recognises (incl. `messenger` and `whatsapp`).
- [ ] `SFSVCExperimental()` is not used. It can report regular Safari as SFSafariViewController.
- [ ] Regular Safari, Chrome (incl. `CriOS`), Firefox (incl. `FxiOS`) and desktop browsers never see the notice.
- [ ] The notice sits above the subscription card and uses shadcn components (`Alert` or a `Card` matching the page). The copy is in Czech in TSX:
    - title "Otevřete stránku v prohlížeči"
    - text explaining that the calendar may not get added inside the app, and that it works from Safari / Chrome
- [ ] On Android the main button "Otevřít v prohlížeči" links to `intent://{host}{path}{query}#Intent;scheme=https;end`, built from the current page URL. There is no `package=` part, so it opens the player's default browser, not necessarily Chrome.
- [ ] On iOS the main button "Otevřít v Safari" links to the current URL with `https://` replaced by `x-safari-https://`. Under it, a short instruction gives the manual path: "Pokud se nic nestane, klepněte na ⋯ a zvolte „Otevřít v prohlížeči“."
- [ ] Both platforms offer "Kopírovat odkaz" for the page URL, reusing `useClipboard`. When the clipboard is blocked, the URL is shown in a selectable read-only input, the same way `CalendarAddress` falls back.
- [ ] Nothing redirects automatically. Every escape is a real tap, because in-app browsers block or hang on navigation without a user gesture.
- [ ] The subscription card below stays fully usable inside the in-app browser. The notice adds a path and blocks nothing.
- [ ] Activity recording from ticket 44 picks up the in-app browser:
    - every event the page sends carries the `inapp-spy` `appKey` as `in_app_browser` (null outside an in-app browser)
    - `TeamPageAction` gains `EscapeIntent`, `EscapeSafari` and `CopyPageLink`, sent when those buttons are tapped
    - the feature and browser tests from ticket 44 cover the new actions
- [ ] Browser tests in `tests/Browser/PublicTeamPageTest.php` (`withUserAgent`) cover:
    - Messenger on Android shows the notice with the `intent://` link to the current page
    - Messenger on iPhone shows the notice with the `x-safari-https://` link and the manual instruction
    - Instagram on Android shows the notice
    - regular Safari on iPhone and Chrome on Android show no notice
    - no horizontal scroll and no JavaScript errors on mobile with the notice shown
- [ ] `composer ci:check` passes.

## Notes

- Decided on 2026-10-09 after the user reported that opening the page from Messenger / WhatsApp on a phone jumps through an app and then the default browser before subscribing.
- The user decided against a Pennant feature flag. The page is public, nobody is signed in inside an in-app browser, and they don't want a per-team or per-cookie scope. The notice ships to everyone in an in-app browser.
- The user approved `inapp-spy` for detection on 2026-10-09. It is the most used library for this (~34k weekly npm downloads vs. ~7k for the unmaintained `detect-inapp`, ~1k for `eiab`), MIT, maintained, and recognises Messenger and WhatsApp separately. `ua-parser-js` v2 also detects in-app browsers but is AGPL-3.0, so it was rejected. `inapp-spy` does no escaping, so the `intent://` / `x-safari-https://` links are ours.
- WhatsApp: on Android it most likely opens links in Chrome Custom Tabs, and possibly SFSafariViewController on iOS. Both run the real browser engine, so `webcal://` and Google sign-in should mostly work there. `inapp-spy` flags WhatsApp when its UA says so. If the real-device check shows that the notice in WhatsApp gets in the way more than it helps, skip it with `skip: [{ appKey: 'whatsapp' }]`. Send yourself https://inappdebugger.com in each app to see what it reports.
- Escape links are best-effort and change with app versions:
    - `intent://` is reported as reliable on Android.
    - `x-safari-https://` works on iOS 15, 17 and 18, not on iOS 16. Meta's iOS in-app browsers may ignore it or hang, which is why the manual "⋯" instruction and "Kopírovat odkaz" are always shown.
- Manual check on real phones before closing (the user tests this by hand). Open a `/t/{slug}` link sent in Messenger and in WhatsApp on Android and iPhone. Note whether the notice appears and where each button lands, then subscribe via Google Kalendář and iPhone / Mac. Record the UA strings seen in each app in a comment below.
- Sources: [eiab](https://github.com/anaclumos/eiab), [The Pitfalls of In-App Browsers](https://frontendmasters.com/blog/the-pitfalls-of-in-app-browsers/), [Escaping Instagram's In-App Browser on iOS](https://dev.to/jplogix/escaping-instagrams-in-app-browser-on-ios-and-why-its-so-hard-58om), [Safari URL scheme on iOS](https://christiantietze.de/posts/2023/05/safari-for-mac-url-scheme/), [Android Chrome intents](https://www.branch.io/resources/blog/technical-guide-to-android-chrome-intents/), [Passkeys in in-app browsers (UA tokens, Google `disallowed_useragent`)](https://www.corbado.com/blog/passkeys-in-app-browsers).
