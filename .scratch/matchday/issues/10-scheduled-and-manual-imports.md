# 10 — Scheduled and manual imports

**What to build:** Fixture lists stay current without the administrator doing anything, and two imports of one team season never overlap. Every three hours each team season of the current season with auto import enabled is imported through a queued job. The same job can be dispatched manually; the "Synchronizovat" button itself comes with the fixture list page. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module → Triggers and → Concurrency).

**Blocked by:** 05 — Import a fixture list

**Status:** ready-for-agent

- [ ] A queued job imports one team season with a given trigger and is unique per team season.
- [ ] The Artisan command imports one given team season, or, without one, dispatches the job for every team season of the current season with auto import enabled.
- [ ] The schedule runs the command every three hours.
- [ ] Team seasons outside the current season or with auto import disabled are not imported by the schedule; a manual import works for any team season.
- [ ] Feature tests assert which team seasons get a job (`Queue::fake()`), the trigger passed, that the job is unique per team season, and that the command is scheduled.
- [ ] `composer ci:check` passes.
