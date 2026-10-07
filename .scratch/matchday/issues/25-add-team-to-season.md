# 25 — Add a team to a season

**What to build:** The administrator starts tracking a team in the selected season. A brand-new team needs the address of its fixture list on ceskyflorbal.cz and a slug, which becomes its permanent calendar address. A team from a previous season needs only the new fixture list address, so its slug and calendar address carry over and players stay subscribed. The first import starts right away, so the fixture list, the team's name and its competition appear without typing them.

**Blocked by:** 24 — Týmy: the selected season's team seasons

**Status:** resolved

- [x] From Týmy the administrator adds a new team with the fixture list address and a slug.
- [x] While choosing the slug, the form previews the resulting calendar address and warns that the slug must not change later.
- [x] From Týmy the administrator carries over a team from a previous season that isn't in the selected season yet, entering only the new fixture list address; the existing team (and its slug) gets a new team season.
- [x] The federation's team ID is taken from the fixture list address; an address that isn't a ceskyflorbal.cz team fixture list is rejected.
- [x] Validation through form requests: a unique slug in a URL-safe format, a unique federation team ID, one team season per team and season.
- [x] The team season's name and competition stay empty until its first import fills them; a team may have a different name in each season.
- [x] Adding the team season dispatches its first import (manual trigger) right away.
- [x] UI is built from shadcn components (Dialog or Sheet, Form fields, Input, Select, Alert, …).
- [x] Feature tests cover: adding a new team, carrying a team over (same team, new team season), the ID parsed from the address, each validation failure, the first import dispatched (`Queue::fake()`), and login required.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Schema" (teams, team_seasons), "Admin pages" (Týmy), user stories 45–50. Carrying a team over replaces the old per-team "new season" action.
- The import aborts when the page's season differs from the team season's season, which catches a forgotten address change.
