# 04 — Demo seeder

**What to build:** Running `php artisan migrate:fresh --seed` fills the database with a realistic situation, so that every later page can be developed and checked visually on believable data. The data mirrors the examples in the spec (`.scratch/matchday/spec.md`) and the real FBC Kutná Hora B fixture list.

**Blocked by:** 03 — Imports and revisions schema

**Status:** resolved

- [x] Seasons 2025/26 and 2026/27 exist; 2026/27 is the current season.
- [x] The team with slug `fbc-kutna-hora-b` (derived from its name) has a team season in both seasons, each with a different federation team ID (42058 for 2025/26 and 45019 for 2026/27) and a matching source URL. The 2026/27 team season is named "FBC Kutná Hora B" in the "PH a SČ liga mužů".
- [x] The 2026/27 team season has about 24 fixtures covering every state:
    - finished with scores (wins, losses, a draw),
    - scheduled with a known time,
    - scheduled with a TBD time,
    - a rescheduled fixture,
    - a postponed fixture,
    - a cancelled fixture.
    - Both home and away fixtures are included.
- [x] Venues include some with an address and some without.
- [x] Imports of the 2026/27 team season:
    - an initial import (ok, fixtures added, no revisions),
    - an ok import with revisions that is already notified,
    - an ok import with revisions that is not notified yet,
    - an aborted import ("Parser vrátil 0 zápasů (minule 24)"),
    - an error import ("HTTP 403 – požadavek zablokován"),
    - at least one manual import.
- [x] Revisions cover a TBD → time change, a date + venue + rescheduled change, a status change and a "fixture added" revision.
- [x] The 2025/26 team season has a handful of finished fixtures, so past-season browsing has data.
- [x] The existing user seeding from the starter kit keeps working.
- [x] `migrate:fresh --seed` runs without errors, and `composer ci:check` passes.
