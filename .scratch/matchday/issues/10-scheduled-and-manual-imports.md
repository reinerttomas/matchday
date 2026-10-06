# 10 — Scheduled and manual imports

**What to build:** Fixture lists stay current without the administrator doing anything, and two imports of one team season never overlap. Every four hours the scheduler runs the import command, which imports each team season of the current season with auto import enabled, one after another. A queued job imports one team season for the manual "Synchronizovat" action; the button itself comes with the fixture list page. An import is skipped while another import of the team season is running, and a `running` import older than 15 minutes is treated as dead. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module → Entry point, → Triggers and → Concurrency).

**Blocked by:** 05 — Import a fixture list

**Status:** resolved

- [x] Without a team season, the Artisan command imports each team season of the current season with auto import enabled, one after another, with trigger `schedule`. Team seasons outside the current season or with auto import disabled are not imported.
- [x] With a team season, the command imports it with trigger `manual`, whatever its season or auto import setting.
- [x] The schedule runs the command every four hours without overlapping.
- [x] A queued job imports one team season with trigger `manual`. It allows 300 seconds and is not retried.
- [x] An import of a team season that already has a `running` import is skipped: no import is created, and the command says so. The check and the creation of the new `running` import happen under a short atomic lock.
- [x] A `running` import older than 15 minutes ends as `error` with the reason "Import nebyl dokončen.", it is logged as an error with no email sent, and the new import goes ahead.
- [x] Feature tests cover which team seasons the command imports and with which trigger, the schedule, the job, skipping while an import is running, and the dead `running` import.
- [x] `composer ci:check` passes.
