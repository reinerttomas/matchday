# 32 — Fill in the venue of finished fixtures

**What to build:** A fixture that was already finished when an import first saw it gets its venue from the match detail page, so Rozpis zápasů, the public page and the calendar show where it was played. Today these fixtures never get a venue: the federation's fixture list shows no venue on finished rows, and the import downloads the match detail page only for rows that show one (`ImportTeamSeason::matchDetails()`). Fixtures imported while still scheduled keep the venue they had, so only fixtures finished before their first import are affected (locally the four fixtures of 19. 9. and 4. 10.).

**Blocked by:** —

**Status:** resolved

- [x] The import downloads the match detail page (`/match/detail/info/{id}`) also for a row with no venue in the fixture list when the fixture has no stored venue (including a fixture new to the app). Rows that show a venue keep today's rule (download when it differs from the stored venue's name).
- [x] A fixture that already has a venue is never downloaded again because of a finished row, so each such fixture costs one request, once.
- [x] The warning "The match detail page names the venue differently than the fixture list…" is logged only for rows that show a venue; a row with no venue has nothing to compare.
- [x] A failed or venue-less match detail page is skipped as today; the fixture stays without a venue and the next import tries again.
- [x] Filling in the missing venue of a finished fixture is a backfill, not a change: it records no revision, so it shows neither on Změny nor as "změněno" on Rozpis zápasů, and it is not announced in a change summary. Filling in or changing the venue of any other fixture records a revision as today.
- [x] Venues are created or updated by external ID exactly as today (name updated, a missing address filled in, an existing address never overwritten).
- [x] The spec's Venues section (`.scratch/matchday/spec.md`, the bullet on when the Informace tab is fetched) is updated to the new rule.
- [x] Feature tests cover: a finished row without a stored venue gets the venue from the match detail page with no revision; a finished row whose fixture already has a venue triggers no download; a scheduled row's venue change still records a revision; a failed match detail page leaves the venue empty and the import ok.
- [x] `composer ci:check` passes.

## Notes

- Verified 2026-10-07: `CeskyflorbalClient::matchDetail(1306729)` (finished 19. 9. fixture) parses with the existing `MatchDetailParser` to venue 602 "SH Kutná Hora Klimeška", "Čáslavská 274, Kutná Hora". The match summary (`/match/detail/default/{id}`) links to that Informace tab; no parser change is needed.
- Downloads stay paced by the client like other match detail requests. A whole season imported after a few rounds adds one request per finished fixture to its first import only.
- Found while prototyping Rozpis zápasů layouts (branch `prototype/fixture-list-layouts`): finished fixtures showed "–" in the venue column.
