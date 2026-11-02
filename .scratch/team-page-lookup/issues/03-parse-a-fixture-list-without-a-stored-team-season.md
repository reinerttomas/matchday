# 03 — Parse a fixture list without a stored team season

**What to build:** A prefactor for the lookup (ticket 04). The fixture list parser needs a stored team season today: for the team's own link in a fixture row, for the season's start year it puts on fixture dates such as "NE, 4. 10.", and for a log context. The lookup has to parse a team that is not stored yet. Make the parser take the federation team ID instead, with the import unchanged. See `.scratch/team-page-lookup/spec.md`.

**Blocked by:** 01 — Derive federation team page addresses from the ID and drop `source_url` (both change the client's fixture list download)

**Status:** ready-for-agent

- [ ] The parser's interface is `parse($html, $externalId)`. It finds the team's own link and name in fixture rows by the federation team ID.
- [ ] The season's start year comes from the season in the page's team header (such as "PH A SČ LIGA MUŽŮ 2026/2027"), read before the rows are parsed. The import is not affected: a page whose header shows another season aborts with `season_mismatch` before its fixture dates are used.
- [ ] The warning about an unknown fixture status logs `external_id` instead of `team_season_id`.
- [ ] The import keeps calling the client with the team season, and the client passes the team season's `external_id` to the parser.
- [ ] No new tests: the import and manual import tests cover the parser through HTTP and keep passing unchanged.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
