# 20 — Synchronizovat: a manual import from the admin

**What to build:** The administrator can download the selected team season's fixture list on demand instead of waiting for the next scheduled import. They see how fresh the data is, the button can't be pressed twice while an import runs, and the page refreshes by itself once the import finishes, so nobody reloads by hand.

**Blocked by:** 18 — Admin shell and the Importy page; 19 — Rozpis zápasů: the stored fixture list

**Status:** resolved

- [x] A "Synchronizovat" button on Rozpis zápasů and on Importy dispatches the queued import job for the selected team season with trigger manual, and returns right away.
- [x] The never-imported empty state on Rozpis zápasů offers the same action.
- [x] A manual import works for a team season outside the current season.
- [x] Under the Rozpis zápasů heading: "Naposledy staženo před …" (relative to the last finished import), or "Stahuji rozpis…" while an import of the team season runs.
- [x] The button is disabled while an import of the team season runs.
- [x] While an import runs, the page polls and stops polling once it has finished, showing the new data and import without a manual reload.
- [x] Pressing the button while an import is already running doesn't start a second one (the import module already skips it); the action doesn't fail.
- [x] UI is built from shadcn components (Button, Spinner, …).
- [x] Feature tests cover: the action dispatches the job for the selected team season (`Queue::fake()`), it requires login, the running/not-running props that drive the button and the heading, and "Naposledy staženo" with `travelTo()`.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Import module" (Triggers, Concurrency), "Admin pages", user stories 56, 57 and 78.
- Polling isn't browser-tested (spec "Testing Decisions"); feature tests cover its server side. Use Inertia v3 polling.
