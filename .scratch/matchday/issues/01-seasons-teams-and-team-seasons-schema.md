# 01 — Seasons, teams and team seasons schema

**What to build:** The data foundation for seasons and our teams. A developer can create a Season, a Team and a Team season (a team's participation in one season) through factories and walk the relationships between them. Pure schema only: migrations, models, relationships and factories. Query helpers such as "the current season" or "a team's current team season" come later, with the tickets that need them. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Schema), `CONTEXT.md` and ADR-0002.

**Blocked by:** None — can start immediately

**Status:** resolved

- [ ] `seasons` table: name (e.g. "2026/27", unique), is_current (boolean, default false), timestamps.
- [ ] `teams` table: slug (unique), timestamps.
- [ ] `team_seasons` table:
    - team (FK), season (FK),
    - external_id: federation team ID, unique,
    - source_url,
    - name and competition_name, both nullable until the first import,
    - auto_import_enabled (boolean, default true),
    - timestamps,
    - unique(team, season).
- [ ] Models Season, Team and TeamSeason with relationships: a season has many team seasons, a team has many team seasons, a team season belongs to a team and a season. Casts are in place for the boolean columns.
- [ ] Factories:
    - Season, with a `current` state,
    - Team,
    - TeamSeason, with a state for "not imported yet" (name and competition_name null) and a state for "automatic import disabled".
- [ ] Tests verify that the unique constraints reject a duplicate slug, season name, federation team ID and (team, season) pair.
- [x] `migrate:fresh` works on SQLite; migrations have no `down()` method (forward-only).
- [ ] `composer ci:check` passes, including the architecture tests (final classes, strict types, documented properties).
