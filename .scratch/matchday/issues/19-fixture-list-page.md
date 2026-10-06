# 19 — Rozpis zápasů: the stored fixture list

**What to build:** The administrator sees the selected team season's fixture list exactly as the app has stored it, laid out like the federation's website, so they can compare the two and spot what changed recently. Badges carry meaning: special fixtures, results and recent revisions stand out, ordinary scheduled fixtures stay plain. A warning makes a failed or aborted last import impossible to miss. Starting an import from this page is ticket 20.

**Blocked by:** 18 — Admin shell and the Importy page

**Status:** ready-for-agent

- [ ] `/fixtures` shows the selected team season's fixtures, and the dashboard now redirects here.
- [ ] A switch between upcoming fixtures (today or later in the federation's timezone) and the whole season, each showing its count. Upcoming is the default.
- [ ] Fixtures are grouped by month. Each shows the day and date ("NE 4. 10."), the time or a TBD badge, "Home – Away" with our team in bold, and the venue.
- [ ] A cancelled fixture is struck through.
- [ ] "Dohrávka", "Odloženo" and "Zrušeno" badges mark rescheduled, postponed and cancelled fixtures.
- [ ] A finished fixture shows "Výhra", "Prohra" or "Remíza" from our team's point of view, with the score.
- [ ] An ordinary scheduled fixture has no badge.
- [ ] A fixture revised in the last 7 days has a "Změněno" badge whose tooltip lists its revisions as old → new values, formatted for display (dates, TBD, venue names, status labels).
- [ ] When the last import ended as error or aborted, a warning above the list shows the reason, says the data didn't change, and links to Importy.
- [ ] When the team season has never been imported, an empty state invites the administrator to start an import (the button itself comes in ticket 20).
- [ ] On a phone the fixtures are a list instead of a table.
- [ ] Domain texts the server formats (day and date, month headings, badge labels, revision values) come from `lang/cs/`, reusing what the public page and change summary already format where it fits.
- [ ] UI is built from shadcn components (Table, Badge, Tooltip, Alert, Tabs or ToggleGroup, Empty, …).
- [ ] Feature tests with factories and `travelTo()` cover the props: upcoming vs. whole season and the counts, month grouping, each badge, win/loss/draw from both home and away, the 7-day "Změněno" window at its edges, the tooltip values, the warning after error and after aborted (and none after ok), and the never-imported empty state. No N+1 for revisions.
- [ ] `composer ci:check` passes.

## Notes

- Spec: "Admin pages" (Rozpis zápasů), user stories 55, 58–67.
- Follow the shell, selection and shadcn conventions set by ticket 18.
