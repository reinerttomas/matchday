# 05 — Show the category in the admin and in the calendar name

**What to build:** Same-named teams can be told apart: the admin shows the **category** as a badge next to the team's name, and the calendar is named "<name> (<category>)". Look per prototype variant A on branch `prototype/team-category-ui` (reference only). See `.scratch/team-category/spec.md` ("Team model", "Presenters", "Look" decisions; user stories 21, 23, 31–34).

**Blocked by:** 01 — Give every team a category

**Status:** ready-for-agent

- [ ] Presenters that send teams to the admin UI (team season list, team page, admin selection team switcher, notification settings) send the category (value and label) separately from the name.
- [ ] An outline shadcn `Badge` with the category label right after the team name in the teams table, the sidebar team switcher and the notification settings list. No separate column, no grouping.
- [ ] `Team::calendarName()` returns "<name> (<category label>)"; the calendar feed and the Outlook link use it. `TeamSeason::displayName()` is unchanged, so event titles, change summaries, flash messages and the import failure email keep the plain name.
- [ ] Feature test in the existing calendar feed test: calendar name contains the category, event titles don't. Existing presenter/page tests updated for the category.
- [ ] Browser test: the badge shows next to the team in the teams table.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
