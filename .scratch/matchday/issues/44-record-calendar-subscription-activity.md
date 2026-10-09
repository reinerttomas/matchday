# 44 — Record calendar subscription activity

**What to build:** We want to know how players actually subscribe. Which buttons they tap on the public team page and from which browser, and which calendar apps really fetch the feed afterwards. Record both in our own small tables, without IP addresses, cookies or any visitor identifier. Nothing changes for the player. The admin page that shows the numbers is ticket 45.

**Blocked by:** None (can start immediately)

**Status:** resolved

### Page events (what the player tried)

- [x] A `team_page_events` table and a `TeamPageEvent` model (with a factory) store one row per event. Each row has:
    - `team_id`
    - `action`: a string-backed enum `TeamPageAction` with TitleCase keys: `PageView`, `Google`, `Webcal`, `Outlook`, `CopyAddress`, `HelpOpen`. Ticket 43 adds its escape actions.
    - `in_app_browser`: nullable string. Ticket 43 fills it with the `inapp-spy` `appKey`; until then it is always null.
    - `user_agent`: the request's User-Agent, cut to 512 characters
    - `created_at`
- [x] `POST /t/{team:slug}/events` stores one event and returns `204`.
    - An unknown slug returns 404, an unknown `action` returns 422, and `in_app_browser` is optional, at most 32 characters.
    - The route is excluded from CSRF verification in `bootstrap/app.php`. `navigator.sendBeacon` can't send the token header, and the endpoint is anonymous and stores nothing sensitive.
    - It is throttled (e.g. 30 per minute per IP). The IP is used only for throttling and never stored.
- [x] The public team page sends events with `navigator.sendBeacon`, so a tap that leaves the page still gets recorded:
    - `PageView` once after hydration
    - `Google`, `Webcal`, `Outlook` when the subscribe button is tapped, without delaying or replacing its navigation
    - `CopyAddress` when the address is copied (card or dialog)
    - `HelpOpen` when "Nefunguje to?" opens
    - When `sendBeacon` is missing or returns false, the event is dropped silently.
    - The URL comes from Wayfinder.

### Feed fetches (what really got subscribed)

- [x] A `calendar_fetches` table counts fetches of `/calendar/{slug}.ics` per team, day (Europe/Prague) and User-Agent: `team_id`, `date`, `user_agent` (cut to 512 characters), `user_agent_hash` (sha256 of the cut UA), `count`.
    - It is unique on (`team_id`, `date`, `user_agent_hash`).
    - Calendar apps poll the feed every few hours, so daily counts keep the table small, where one row per fetch would not.
- [x] `CalendarController` records each fetch with an atomic upsert that increments `count`. A failed write never breaks the feed. It is caught and reported, and the feed is still served.

### Retention and privacy

- [x] Both models are `MassPrunable`, keeping 180 days, and `model:prune` is scheduled daily.
- [x] No IP address, cookie or session id is stored in either table.

### Tests and checks

- [x] Feature tests cover:
    - storing an event with and without `in_app_browser`
    - 422 for an unknown action
    - 404 for an unknown slug
    - the UA being cut to 512 characters
    - a feed fetch creating a row and a second fetch from the same UA on the same day incrementing it
    - a fetch the next day or from another UA creating a new row
    - pruning of rows older than 180 days
- [x] A browser test checks that tapping "Google Kalendář" sends a `Google` event (assert the stored row) and that the page still has no JavaScript errors on mobile.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-09. The user chose our own small tables over a package:
    - `laravel/pulse` keeps at most 7 days, too short to compare before and after ticket 43.
    - `shetabit/visitor` is built around visits, not events, and stores IPs by default.
    - Pirsch is an external paid service.
- A universal `events` table (any event name, morph subject, JSON properties, generic admin page) was considered and rejected for now (YAGNI): subscription events are the only ones we measure. If another place needs measuring, generalise `team_page_events` then. `calendar_fetches` stays a separate daily aggregate either way, because of its volume.
- Ship this before ticket 43, so there is baseline data from before the in-app browser notice.
- Keep raw UAs. Which UA belongs to which calendar app (Google, Apple, Outlook) is decided in ticket 45 from the real data, not guessed upfront.
- Google fetches the feed from its servers for all its subscribers at once, so fetch counts show which apps subscribe and the trend, not the number of players.

## Comments

- 2026-10-09: Implemented in f455ee6, then tidied up in 0d82a82. Decisions made during implementation:
    - The throttle is a named limiter `team-page-events` (30/min, keyed by IP) in `AppServiceProvider`, so it doesn't share a key with other inline throttles.
    - `t/*/events` was added to `shouldRenderJsonWhen`. A beacon can't ask for JSON, and without this a validation error would be a 302, not a 422.
    - A missing User-Agent is stored as `''`, so the unique key on `calendar_fetches` still matches it.
    - `help_open` counts only when the player opens the dialog, not when it opens automatically after a blocked copy.
    - `USER_AGENT_MAX_LENGTH` is a constant on each model, and the timezone comes from `services.ceskyflorbal.timezone`.
    - Follow-up, not done: the events route and `calendar/{slug}.ics` run in the `web` group, so they start a session. With `SESSION_DRIVER=database` that row holds the IP, and a calendar app that keeps no cookies creates a new row on every poll. This was already the case before this ticket.
