# 47 — Offer Apple and Google side by side

**What to build:** Since ticket 46, a real browser shows one primary button and folds everything else away under "Jiný kalendář". A player on an iPhone who uses Google Calendar has to find it there, and Macs and iPads read "Přidat do kalendáře v iPhonu". Instead, show both calendars as visible buttons, "Přidat do Apple Kalendáře" and "Přidat do Google Kalendáře", ordered by device. Drop "Jiný kalendář" and the Outlook button, and show "Kopírovat adresu" directly. The in-app browser notice from ticket 46 stays as it is.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] The card shows these buttons, by `useDevice()`:
    - `apple` (iPhone, iPad, Mac): **"Přidat do Apple Kalendáře"** (primary, `webcal`), then **"Přidat do Google Kalendáře"** (outline, `google`, new tab)
    - `android`: only **"Přidat do Google Kalendáře"** (primary), with the existing sync step directly under it
    - `other`: **"Přidat do Google Kalendáře"** (primary), then **"Přidat do Apple Kalendáře"** (outline)
- [ ] "Jiný kalendář", its `Collapsible` and the `other_options_open` action (`TeamPageAction::OtherOptionsOpen` and the TS union member) are removed. The branch isn't released yet, so only local test rows have that action.
- [ ] The Outlook button is removed. `calendar.outlook` stays in `CalendarLinks`, so the server side doesn't change. The `outlook` action stays in the enum, because past rows may hold it.
- [ ] "Kopírovat adresu" (`copy_address`) and "Nefunguje to?" sit in the card footer, visible without unfolding anything, as before ticket 46.
- [ ] The help dialog steps name the new buttons:
    - Google tab: "Klikněte na tlačítko „Přidat do Google Kalendáře“ a přihlaste se ke svému účtu Google."
    - Android tab, first step: "…klepněte na „Přidat do Google Kalendáře“…"
    - iPhone tab: "Klepněte na tlačítko „Přidat do Apple Kalendáře“."
    - Outlook tab, first step: "Zkopírujte adresu kalendáře tlačítkem „Kopírovat adresu“." The step after it is the existing manual path ("v Outlooku kalendář → „Přidat kalendář“ → „Přihlásit se k odběru z webu“, vložte zkopírovanou adresu a potvrďte"). There is no Outlook button any more.
- [ ] Browser tests in `tests/Browser/PublicTeamPageTest.php`, updating the ticket-46 tests that assume one primary button and "Jiný kalendář":
    - iPhone: Apple first with a `webcal:` href, Google second.
    - Mac: Apple first.
    - Android: only Google, with the sync step.
    - desktop: Google first, Apple second.
    - On every device: no Outlook button, no "Jiný kalendář", "Kopírovat adresu" visible right away.
    - Taps still record `webcal` / `google` / `copy_address`.
    - no horizontal scroll and no JavaScript errors on mobile.
- [ ] `composer ci:check` passes.

## Notes

- Decided on 2026-10-09 with a UI prototype. The user wanted both Apple and Google available, so an iPhone user on Google Calendar doesn't have to hunt for it. Three variants were compared: A today's card, B two visible buttons, C two calendar tiles with descriptions. B won.
- The prototype is on the branch `prototype/subscribe-buttons` (commit `0a62701`). See `VariantB` in `resources/js/components/calendar-subscription-prototype.tsx` for the look. Do not merge the branch. It also has `?device=` simulation in `use-device.ts`, which is handy for checking the card by hand while running `pnpm dev`.
- Apple is never offered on Android, because the Apple calendar doesn't exist there.
- "Přidat do Apple Kalendáře" also settles the open point from ticket 46: Mac and iPad no longer read "v iPhonu".
