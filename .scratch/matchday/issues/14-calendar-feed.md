# 14 — Calendar feed for a team's current season

**What to build:** Each team has a permanent public calendar address, `/calendar/{slug}.ics`, which a player subscribes to once in Google, Apple or Outlook calendar. The feed serves the fixtures of the team's team season in the **current** season, so the address keeps working across seasons and last season's fixtures disappear once a new season becomes current. When a fixture is revised, its existing event is updated rather than duplicated. This ticket covers ordinary fixtures with a known start time. The special variants (TBD time, finished, postponed, cancelled, rescheduled) are ticket 15.

**Blocked by:** None — can start immediately

**Status:** resolved

- [x] Prefactor: the "Home – Away" naming (our team season's name vs. the opponent, depending on `is_home`), now inline in `ChangeSummaryWriter`, lives on `Fixture`, and the change summary uses it. Change summary tests pass unchanged.
- [x] `spatie/icalendar-generator` is installed and generates the feed.
- [x] `GET /calendar/{slug}.ics` is public (no auth) and answers with a `text/calendar` response that calendar apps accept as a subscription.
- [x] The feed contains every fixture of the team's team season in the current season, finished fixtures included, and no fixtures from other seasons.
- [x] A team without a team season in the current season gets a valid calendar with no events, not an error.
- [x] An unknown slug returns 404.
- [x] Each event has a UID that is stable per fixture row (the same across requests and imports) and SEQUENCE equal to the fixture's `sequence`.
- [x] Title: "Home – Away".
- [x] A fixture with a known time starts at that time in Europe/Prague (TZID, not floating or UTC-shifted) and lasts 60 minutes, an application constant.
- [x] Location: the venue name, followed by its address when the venue has one; no location when the fixture has no venue. An address an administrator edited shows up in the next feed response.
- [x] Description: the team season's competition, "N. kolo" when the round is known, and a link to the fixture's match detail page on ceskyflorbal.cz.
- [x] Every text the feed produces comes from `lang/cs/`, following ticket 12.
- [x] Feature tests call the route with factory data and cover: event content, UID/SEQUENCE, the 60-minute Prague-time event, venue with and without an address, a fixture from another season left out, the empty calendar outside the current season, and 404.
- [x] `composer ci:check` passes.

## Notes

- The user approved adding `spatie/icalendar-generator` (spec "Further Notes" left hand-written ICS vs. this package open).
- Spec: "Calendar feed (ICS)", user stories 1–4, 6, 7, 13–17 and 84.
