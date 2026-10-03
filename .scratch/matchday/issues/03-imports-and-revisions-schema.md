# 03 — Imports and revisions schema

**What to build:** Storage for imports and revisions. A developer can record an import of a team season in any state, together with the revisions it produced: field changes and added fixtures. Pure schema only: migrations, models, enums, casts, relationships and factories. Logic such as "which import is the initial import", the unsent count or the "changed in the last 7 days" window comes later. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Schema and → Revisions), `CONTEXT.md` and ADR-0001.

**Blocked by:** 02 — Venues and fixtures schema

**Status:** resolved

- [x] `imports` table:
    - team season (FK),
    - trigger, status,
    - started_at, finished_at (nullable),
    - fixtures_found (nullable),
    - error (nullable text),
    - notified_at (nullable).
    - There is no stored revision count.
- [x] `revisions` table:
    - fixture (FK), import (FK),
    - field: nullable, null = fixture added,
    - old_value and new_value (nullable strings),
    - created_at.
- [x] Backed enums, cast on the models:
    - import trigger: schedule, manual,
    - import status: running, ok, error, aborted,
    - revision field: date, time, venue, status, is_rescheduled, home_score, away_score.
- [x] Models Import and Revision:
    - relationships: a team season has many imports, an import has many revisions, a fixture has many revisions, a revision belongs to a fixture and to an import,
    - casts for the timestamps and enums.
- [x] Factories:
    - Import (ok by default), with states for running, error (with a reason), aborted (with a reason), manual trigger and notified,
    - Revision, with a state for a field change (old → new value) and a state for "fixture added".
- [x] `migrate:fresh` works on SQLite; migrations have no `down()` method (forward-only).
- [x] `composer ci:check` passes.
