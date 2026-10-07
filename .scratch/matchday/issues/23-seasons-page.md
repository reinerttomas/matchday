# 23 — Sezony: create a season and make it current

**What to build:** The administrator prepares the next season before it starts and decides the moment every calendar and the automatic imports switch to it, by marking it as current.

**Blocked by:** 18 — Admin shell and the Importy page

**Status:** resolved

- [x] `/seasons` lists the seasons, newest first, with the current one marked and the number of team seasons in each.
- [x] The administrator creates a season by its name in the "2027/28" format (two consecutive years); the name is unique and validated through a form request.
- [x] The administrator marks a season as current; the previously current season stops being current in the same transaction, so exactly one season is current.
- [x] After a season becomes current, calendars and the public pages serve that season and scheduled imports cover its team seasons (already true through the `is_current` flag; covered by a test).
- [x] Creating a season doesn't change the admin's selected season; marking a season current doesn't change it either.
- [x] UI is built from shadcn components (Table, Dialog or inline form, Input, Button, Badge, …).
- [x] Feature tests cover: the list props, creating a season, name validation (format, uniqueness), marking current (exactly one current afterwards), and login required.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Admin pages" (Sezony), user stories 43 and 44. The dedicated `/seasons` page was "decided while writing this spec" — revisit with the user if it feels wrong.
