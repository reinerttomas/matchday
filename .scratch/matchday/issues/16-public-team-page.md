# 16 — Public team page with upcoming fixtures

**What to build:** Each team has a public page, `/t/{slug}`, that a player opens from a shared link or QR code, mostly on a phone. It shows the team's team season in the **current** season: the team name, competition and season, and the upcoming fixtures grouped by match day, so a player can scan the schedule at a glance. The page reads comfortably on a phone and in light and dark mode. Subscribing to the calendar (buttons, instructions, QR code) is ticket 17.

**Blocked by:** None — can start immediately

**Status:** resolved

- [x] Prefactor: finding a team's team season in the current season, and naming a fixture's round ("N. kolo" / "dohrávka N. kola"), now inline in `CalendarWriter`, are reusable outside it, and the feed uses them. Calendar feed tests pass unchanged.
- [x] `GET /t/{slug}` is public (no auth) and renders an Inertia page; an unknown slug returns 404.
- [x] The heading shows the team season's name, competition and season.
- [x] Upcoming fixtures are those dated today or later in Europe/Prague, in date and time order.
- [x] Fixtures are grouped by match day under a heading such as "Neděle 4. října", with the year added when the date isn't in the current year.
- [x] When every fixture of a match day is at the same venue, the venue appears once in the match day's heading; otherwise each fixture shows its own venue.
- [x] Each fixture shows its start time, or "TBD" for a TBD time, and "Home – Away" with our team in bold.
- [x] A rescheduled fixture is labelled "dohrávka N. kola", matching the calendar.
- [x] Postponed and cancelled fixtures stay in the list with an "Odloženo" / "Zrušeno" label, so nobody turns up on the old date. A finished fixture shows its score.
- [x] The first four match days are shown; a "Zobrazit celou sezonu" button reveals the rest of the season's upcoming fixtures.
- [x] The footer shows the data source (a link to the fixture list on ceskyflorbal.cz), the time of the last successful import, and what TBD means.
- [x] Empty states: "Sezona skončila" when the team season has no upcoming fixtures; "Pro sezonu X zatím není rozpis" (X = the current season's name) when the team has no team season in the current season.
- [x] Texts the server formats (match day headings, round labels, "Home – Away", fixture labels) come from `lang/cs/`, following ticket 12; static UI copy stays in Czech in the page component.
- [x] Feature tests call the route with factory data and `travelTo()` and cover the page props: heading, upcoming vs. past fixtures, match day grouping and the year in the heading, the shared vs. per-fixture venue, the rescheduled, postponed, cancelled and finished labels, the last update time, both empty states and 404.
- [x] A browser test covers "Zobrazit celou sezonu" expanding beyond four match days, and on a mobile viewport no horizontal scroll and no JavaScript errors.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Public team page", user stories 18, 24–32 and 42 (phone, light and dark mode).
- Decided with the user: postponed and cancelled fixtures are shown with a label rather than hidden. Domain texts are formatted on the server from `lang/cs/` and sent as props; static UI copy (headings, button labels) is written in Czech in TSX.
- Link to the page with Wayfinder, not a hardcoded URL.
