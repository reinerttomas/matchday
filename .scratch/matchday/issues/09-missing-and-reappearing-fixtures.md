# 09 — Missing and reappearing fixtures

**What to build:** A fixture removed from the source disappears from calendars, but a one-off source glitch doesn't cancel games, and mistakes heal themselves. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module → Applying, user stories 92–94).

**Blocked by:** 06 — Revisions on re-import

**Status:** ready-for-agent

- [ ] Every fixture seen in an import gets `missing_count` reset to 0.
- [ ] A non-cancelled fixture of the team season not seen in an import gets `missing_count` incremented and is otherwise untouched.
- [ ] At `missing_count` 2 the fixture becomes `cancelled` and a status revision is written (with `sequence` incremented).
- [ ] A cancelled fixture that reappears takes its status from the source again, recorded as a revision.
- [ ] Feature tests run consecutive imports through the command with snapshot variants: missing once, missing twice, reappearing after cancellation.
- [ ] `composer ci:check` passes.
