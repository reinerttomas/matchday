# 24 — Týmy: the selected season's team seasons

**What to build:** The administrator sees every team season of the selected season at a glance — what it is called, which competition it plays, its slug, how its last import went — and can stop a team season's automatic import while its calendar keeps serving the last data. Common tasks for a team are one click away. Adding a team is ticket 25.

**Blocked by:** 18 — Admin shell and the Importy page

**Status:** ready-for-agent

- [ ] `/teams` lists the selected season's team seasons with name, competition, slug, last import time (with a badge only when it didn't end ok) and an automatic import toggle.
- [ ] A team season not imported yet shows its slug as the name and no competition.
- [ ] Toggling automatic import updates `auto_import_enabled` without leaving the page; scheduled imports skip a disabled team season while its calendar keeps working.
- [ ] Team actions: copy the calendar address, open the public page, open the fixture list on ceskyflorbal.cz.
- [ ] On a phone the Competition and Slug columns are hidden and the last import is shown under the team name.
- [ ] UI is built from shadcn components (Table, Switch, DropdownMenu, Badge, …).
- [ ] Feature tests cover: the list props for the selected season only, the last-import badge (none after ok, shown after error and aborted), the toggle action with validation through a form request, and login required.
- [ ] `composer ci:check` passes.

## Notes

- Spec: "Admin pages" (Týmy), user stories 51–54.
- Absolute calendar and public page URLs come from the server, as on the public page (ticket 17).
