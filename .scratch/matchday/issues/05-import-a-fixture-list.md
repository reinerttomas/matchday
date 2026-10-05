# 05 — Import a fixture list

**What to build:** The tracer bullet of the import module. Running an Artisan command for one team season downloads its fixture list from ceskyflorbal.cz, parses it and stores the fixtures, so the team season's fixture list in the database matches the source. The import is recorded with its trigger, times, result and the number of fixtures found. Venues are not resolved yet (`venue_id` stays null). Revisions, the abort rules, missing fixtures, scheduling and emails come in later tickets. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module, Testing Decisions → Seam 1), `CONTEXT.md` and ADR-0002.

**Blocked by:** 04 — Demo seeder

**Status:** resolved

- [x] The import module is a deep module whose interface is "import this team season with this trigger", returning the finished Import. Callers (the command now, the job later) know nothing about fetching or parsing.
- [x] An Artisan command imports one given team season synchronously with trigger `schedule`, and prints the result (status, fixtures found).
- [x] The import is recorded as `running` when it starts and finishes as `ok` with `finished_at` and `fixtures_found`.
- [x] The fixture list page is fetched with Laravel's HTTP client and a browser-like User-Agent.
- [x] Parsing the fixture list page (facts in the spec), using PHP's built-in HTML DOM, no new package:
    - only the first occurrence of each row's date and round is used (rows repeat them in mobile and desktop wrappers),
    - external ID from the match detail link,
    - date "NE, 4. 10." with the year inferred from the team season's season: July–December → first year, January–June → second year,
    - time HH:MM, where 00:00 is stored as a TBD time (null),
    - `is_home` when the home team's ID equals the team season's `external_id`; the opponent name is the other side,
    - round from "N. kolo",
    - "odehráno" in the status slot sets status finished, with the score home:away; finished rows have no time, so the stored time is kept,
    - a row is scheduled only when it shows a venue and no status text,
    - the warning icon sets `is_rescheduled`; the fixture stays scheduled,
    - unknown status markup leaves the status unchanged and is logged.
- [x] Fixtures are upserted by (team season, external ID); the team season's `name` and `competition_name` are updated from the page.
- [x] HTML snapshots of the live fixture list page (FBC Kutná Hora B, 2026/27) are saved for tests. If the live site blocks the download, stop and ask the orchestrator instead of inventing markup.
- [x] Feature tests run the import through the command with `Http::fake()` returning the snapshot and assert the stored fixtures (TBD time, finished with score, rescheduled, home and away), the team season name and competition, and the recorded import. Year inference is covered with `travelTo()` where it matters.
- [x] `composer ci:check` passes.
