# 02 — Venues and fixtures schema

**What to build:** Storage for venues and fixtures. A developer can create fixtures in every state (TBD time, finished with a score, postponed, cancelled, rescheduled, home or away) for a team season, at a venue with or without an address. Pure schema only: migrations, models, the status enum, casts, relationships and factories. Domain logic such as the result from our team's point of view, start time or display names comes later. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Schema), `CONTEXT.md` and ADR-0002.

**Blocked by:** 01 — Seasons, teams and team seasons schema

**Status:** ready-for-agent

- [ ] `venues` table: external_id (federation arena ID, unique), name (not unique), address (nullable), timestamps.
- [ ] `fixtures` table:
    - team season (FK),
    - external_id: federation match ID,
    - round (nullable),
    - is_home, opponent_name,
    - venue (nullable FK),
    - date: local Europe/Prague,
    - time: nullable, null = TBD,
    - status,
    - is_rescheduled (default false),
    - home_score and away_score (nullable),
    - sequence (default 0), missing_count (default 0),
    - timestamps,
    - unique(team season, external_id),
    - index on (team season, date).
- [ ] A backed fixture status enum with the cases scheduled, postponed, finished and cancelled, cast on the model.
- [ ] Models Venue and Fixture:
    - relationships: a fixture belongs to a team season and to a venue (optional), a team season has many fixtures, a venue has many fixtures,
    - casts for date, booleans and the status enum.
- [ ] Factories:
    - Venue, with states for "with address" and "without address",
    - Fixture, with states for TBD time, finished (with a score), postponed, cancelled, rescheduled, home and away.
- [ ] Tests verify that the unique constraints reject a duplicate federation arena ID and a duplicate (team season, federation match ID) pair, and that the same federation match ID is allowed in two different team seasons (ADR-0002).
- [ ] `migrate:fresh` works on SQLite; migrations have no `down()` method (forward-only).
- [ ] `composer ci:check` passes.
