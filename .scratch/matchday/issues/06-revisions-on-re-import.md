# 06 — Revisions on re-import

**What to build:** When a team season is imported again, every change of a revisable fixture field is recorded as a revision, so the administrator can later see what changed and the calendar can update existing events. A fixture that appears after the initial import is recorded as added. An import that finds nothing different records nothing. Venue revisions come with ticket 07; missing fixtures with ticket 09. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Revisions and → Import module → Applying), `CONTEXT.md` and ADR-0001.

**Blocked by:** 05 — Import a fixture list

**Status:** ready-for-agent

- [ ] A changed value of date, time, status, is_rescheduled, home_score or away_score writes one revision per field with old and new raw values: ISO date, `HH:MM` or null for TBD, the status enum value, `0`/`1` for is_rescheduled (the convention the demo seeder already uses), the score as a number.
- [ ] A fixture with at least one revision in an import gets its `sequence` incremented exactly once for that import.
- [ ] The initial import (the team season's first `ok` import) stores its fixtures without revisions.
- [ ] A fixture that appears after the initial import gets one revision with `field = null`.
- [ ] A second import of an identical page writes no revisions and leaves `sequence` unchanged.
- [ ] Fixtures and revisions are applied in a single database transaction.
- [ ] Feature tests run two imports through the command with snapshot variants (changed time, TBD → time, finished with score, warning icon appearing, a new fixture, identical page) and assert revisions and `sequence`.
- [ ] `composer ci:check` passes.
