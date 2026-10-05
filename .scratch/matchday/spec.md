Status: ready-for-agent

# Matchday — fixture calendar for floorball teams

## Problem Statement

A team's fixture list lives on ceskyflorbal.cz. The federation keeps changing it during the season: it fills in start times (a fixture with no time yet shows 00:00), switches venues and moves fixtures to other dates. Players copy the fixture list into their own calendars by hand and find out about changes late, or not at all. The one person who looks after the team has no easy way to notice what changed and tell the team.

## Solution

A web app that downloads the fixture lists of our teams from ceskyflorbal.cz every three hours, stores them, and spots what changed since the previous import.

- Each team gets a permanent public calendar address. Players add it to Google, Apple or Outlook calendar once, and from then on changes reach them automatically.
- Each team gets a public page that explains how to add the calendar and lists upcoming fixtures.
- For every import that found changes, the administrator gets a ready-made change summary, which they send to the team's WhatsApp group in one tap and then mark as sent.
- The administrator manages seasons and teams. Each season they add our teams to it, either new or carried over from the previous season. They can switch between seasons and teams to browse any team's fixture list, revisions and imports, past seasons included.

Domain vocabulary follows `CONTEXT.md`: Season, Team, Team season, Fixture, Opponent, Venue, Import, Initial import, Revision, Change summary, Rescheduled / Postponed / Cancelled fixture, TBD time. The data model follows ADR-0001 (revisions instead of activitylog) and ADR-0002 (fixtures owned by team season).

## User Stories

### Player — calendar

1. As a player, I want to add my team's calendar to Google Calendar from a link, so that fixtures appear next to my other events.
2. As a player, I want to add the calendar to my iPhone or Mac calendar, so that I can see fixtures in the Apple Calendar app.
3. As a player, I want to add the calendar to Outlook, so that I can use the calendar I already have.
4. As a player, I want to copy the raw calendar address, so that I can add it to any other calendar app.
5. As a player on Android, I want separate instructions, so that I can subscribe even though the Google Calendar Android app cannot add a calendar from a link.
6. As a player, I want a fixture whose time changed to update the existing event in my calendar, so that I never see two events for one fixture.
7. As a player, I want a fixture with a known start time to block 55 minutes, so that my calendar shows when I'm busy.
8. As a player, I want a fixture with a TBD time to be an all-day event marked "(čas TBD)" that doesn't block my day, so that I know a game is coming without a false time.
9. As a player, I want finished fixtures to show the result in the title, so that the calendar doubles as a results history for the season.
10. As a player, I want a rescheduled fixture's description to say "dohrávka X. kola", so that I understand why the round order looks odd.
11. As a player, I want a postponed fixture without a new date to be titled "ODLOŽENO:", so that I don't turn up on the old date.
12. As a player, I want a cancelled fixture to show as cancelled in my calendar, so that I know it won't be played.
13. As a player, I want each event titled "Home – Away", with the venue name and address as its location, so that I can start navigation from the event.
14. As a player, I want the event description to contain the competition, the round and a link to the fixture's page on ceskyflorbal.cz, so that I can check details at the source.
15. As a player, I want my calendar address to stay the same in a new season, so that I never have to re-subscribe.
16. As a player, I want last season's fixtures to disappear from my calendar when the new season becomes current, so that old games don't clutter it.
17. As a player, I want the calendar to stay valid but empty when my team isn't in the current season, so that my calendar app doesn't report an error.

### Player — public team page

18. As a player, I want a public team page, opened from a shared link or QR code, that I can use comfortably on my phone, so that subscribing takes a minute.
19. As a player on an Apple device, I want the "iPhone / Mac" button first, so that the most relevant option is the obvious one.
20. As a player on a non-Apple device, I want the "Google Kalendář" button first, so that the most relevant option is the obvious one.
21. As a player on a wide screen, I want a QR code linking to the page, so that I can open it on my phone.
22. As a player, I want step-by-step instructions in tabs (Google, Android, iPhone, Outlook), so that I can follow the steps for my device.
23. As a player, I want to be told that changes show up within a few hours and are also announced in the WhatsApp group, so that I know what to expect.
24. As a player, I want upcoming fixtures grouped by match day, with the date as the heading ("Neděle 4. října", plus the year when it isn't this year), so that I can scan the schedule quickly.
25. As a player, I want the venue shown once in a match day's heading when all its fixtures share it, and on each fixture otherwise, so that the list stays compact.
26. As a player, I want our team in bold in "Home – Away", so that I can spot our side at once.
27. As a player, I want a rescheduled fixture labelled "dohrávka X. kola" on the page, so that it matches the calendar.
28. As a player, I want the first four match days shown and a "Zobrazit celou sezonu" button for the rest, so that the page stays short.
29. As a player, I want the footer to show the data source, the time of the last update and what TBD means, so that I can trust the information.
30. As a player, I want a "Sezona skončila" message when no upcoming fixtures remain, so that I know the page isn't broken.
31. As a player, I want a "Pro sezonu X zatím není rozpis" message when my team isn't in the current season yet, so that I know to wait.
32. As a visitor, I want an unknown slug to return 404, so that dead links are obvious.
33. As a player, I don't want a calendar-file download offered, so that I don't import a static copy that never updates.

### Administrator — access and layout

34. As the administrator, I want the admin area to require login, so that only I can change things.
35. As the administrator, I want public registration disabled, so that nobody else can create an account.
36. As the administrator, I want the public page and the calendar reachable without login, so that players need no account.
37. As the administrator, I want a sidebar header that switches the selected season and team, so that I can work on any team in any season.
38. As the administrator, I want the selected season and team remembered across pages, so that I don't re-select them on every page.
39. As the administrator, I want the current season selected by default, so that I start where the action is.
40. As the administrator, I want the sidebar grouped into "Tým" (Rozpis zápasů, Změny with the number of unsent change summaries, Importy) and "Nastavení" (Týmy, Haly, Sezony), so that every page has one clear purpose.
41. As the administrator, I want a link to the selected team's public page and my account at the bottom of the sidebar, so that common destinations are one click away.
42. As the administrator, I want every admin page to work on a phone and in light and dark mode, so that I can manage things anywhere.

### Administrator — seasons and teams

43. As the administrator, I want to create a season (e.g. "2027/28"), so that I can prepare the next season before it starts.
44. As the administrator, I want to mark one season as current, so that I decide when every calendar and the automatic imports switch to it.
45. As the administrator, I want to add a brand-new team to a season by entering the address of its fixture list and a slug, so that I can start tracking it.
46. As the administrator, I want to see a preview of the resulting calendar address while choosing a slug, and a warning that the slug must not change later, so that I choose it carefully.
47. As the administrator, I want to add a team from a previous season to a new one with just the new fixture list address, so that its slug and calendar address carry over.
48. As the administrator, I want the team's name and competition for the season loaded by its first import, so that I don't type what the source already knows.
49. As the administrator, I want a team to be allowed a different name in each season, so that renames (for example a new sponsor) are reflected correctly.
50. As the administrator, I want the first import to start right after I add a team to a season, so that I see its fixture list immediately.
51. As the administrator, I want a list of the selected season's teams with name, competition, slug, last import time (with a badge only when it didn't end ok) and an automatic import toggle, so that I can see everything at a glance.
52. As the administrator, I want to switch off automatic import for a team season, so that it stops downloading while its calendar keeps serving the last data.
53. As the administrator, I want team actions to copy the calendar address, open the public page and open the fixture list on ceskyflorbal.cz, so that common tasks are quick.
54. As the administrator, I want the Competition and Slug columns hidden on a phone, with the last import shown under the team name, so that the list fits.

### Administrator — fixture list

55. As the administrator, I want to see the selected team season's fixture list as the app has stored it, so that I can compare it with the federation's website.
56. As the administrator, I want "Naposledy staženo před …" under the heading, or "Stahuji rozpis…" while an import runs, so that I know how fresh the data is.
57. As the administrator, I want a "Synchronizovat" button that starts a manual import, is disabled while one runs, and refreshes the page by itself when it finishes, so that I don't reload by hand.
58. As the administrator, I want to switch between upcoming fixtures and the whole season and see the count, so that I can focus on what's next.
59. As the administrator, I want fixtures grouped by month with day and date ("NE 4. 10."), time or a TBD badge, "Home – Away" with our team in bold, and the venue, so that the list reads like the source.
60. As the administrator, I want a cancelled fixture struck through, so that it stands out.
61. As the administrator, I want a "Změněno" badge on fixtures revised in the last 7 days whose tooltip shows old → new values, so that I can see recent changes in context.
62. As the administrator, I want "Dohrávka", "Odloženo" and "Zrušeno" badges, so that special fixtures stand out.
63. As the administrator, I want finished fixtures to show "Výhra", "Prohra" or "Remíza" with the score, from our team's point of view, so that results are clear.
64. As the administrator, I want ordinary scheduled fixtures to have no badge, so that badges carry meaning.
65. As the administrator, I want a warning above the fixture list when the last import failed or was aborted, with the reason, a note that data didn't change and a link to Importy, so that I notice problems.
66. As the administrator, I want an empty state that invites me to start an import when the team season has never been imported, so that I know what to do.
67. As the administrator, I want a list layout instead of a table on a phone, so that the fixture list is readable there.

### Administrator — changes

68. As the administrator, I want a list of the imports that produced revisions, newest first, so that I can see what changed and when.
69. As the administrator, I want each import to show its revised fixtures (date, "Home – Away") with each revision as "Čas: ~~TBD~~ → 9:00", so that I can read the change at a glance.
70. As the administrator, I want fixtures that appeared after the initial import listed as new fixtures, so that additions such as play-off games get announced.
71. As the administrator, I want the initial import shown last as one collapsed entry ("24 zápasů přidáno do rozpisu") that can be expanded, so that it doesn't flood the list and isn't announced.
72. As the administrator, I want each import with revisions to show either a "Poslat do WhatsAppu" button or "Odesláno týmu {when}", so that I know whether the team has been told.
73. As the administrator, I want a "Souhrn změn pro WhatsApp" dialog with editable text, so that I can tweak the message before sending.
74. As the administrator, I want a "Kopírovat text" button, so that I can paste the summary elsewhere.
75. As the administrator, I want "Otevřít WhatsApp" to open WhatsApp with the (edited) message prefilled, so that I only pick the group and press send.
76. As the administrator, I want "Označit jako odesláno" to appear after I opened WhatsApp, and marking it to remove the import from the sidebar count, so that the count reflects what is left to send.
77. As the administrator, I want "Rozpis se od importu nezměnil" when there are no revisions, so that an empty page is explained.

### Administrator — imports

78. As the administrator, I want the Importy page to say "Rozpis se stahuje z ceskyflorbal.cz každé 3 hodiny." and offer a "Synchronizovat" button, so that I understand and control the schedule.
79. As the administrator, I want a paginated list (20 per page) of the selected team season's imports, newest first, with start time ("ručně" for manual ones), result badge (OK / Chyba / Přerušeno / Probíhá) with the reason under error and aborted, duration, fixtures found and revision count (highlighted when non-zero), so that I can investigate problems.

### Administrator — venues

80. As the administrator, I want venue addresses filled in automatically from the federation's match detail page, so that players can navigate without me typing addresses.
81. As the administrator, I want a venue list with name, address (or a "Doplnit adresu" link), number of fixtures and an edit button, plus a sentence with how many venues lack an address, so that I can fix gaps.
82. As the administrator, I want to edit a venue's address and preview how the location will appear in the calendar, so that I get the format right.
83. As the administrator, I don't want to rename, add or delete venues by hand, so that venues stay in sync with the federation.
84. As the administrator, I want an edited address to show up in the location of calendar events, so that the fix reaches players.

### Administrator — notifications

85. As the administrator, I want an email when an import found revisions, with the change summary text and a WhatsApp link, so that I don't have to check the app.
86. As the administrator, I want an email when an import fails or is aborted, with the reason, so that I can react.

### System — import behaviour

87. As the administrator, I want every active team season of the current season imported automatically every three hours, so that changes are picked up without me.
88. As the administrator, I want only one import of a team season to run at a time, so that concurrent imports can't corrupt data.
89. As the administrator, I want each import recorded with trigger, start and end time, result, fixtures found and its revisions, so that I have a history.
90. As the administrator, I want a 00:00 time treated as a TBD time, so that it isn't presented as midnight.
91. As the administrator, I want a fixture with the warning icon stored as a rescheduled fixture that stays scheduled, so that it appears as "dohrávka".
92. As the administrator, I want a fixture missing from the source once left untouched, so that a source glitch doesn't cancel games.
93. As the administrator, I want a fixture missing in two consecutive imports to be cancelled (recorded as a revision), so that removed games disappear from calendars.
94. As the administrator, I want a cancelled fixture that reappears to take its state from the source again (recorded as a revision), so that mistakes heal themselves.
95. As the administrator, I want an import aborted without changing data when the source returns no fixtures or fewer than half of the last successful import's count, so that a broken page can't wipe the calendar.
96. As the administrator, I want an import to end with an error without changing data when the download fails (e.g. HTTP 403), so that blocks by the bot protection are visible.
97. As the administrator, I want an import aborted when the page's season differs from the team season's season, so that a forgotten URL change after copying a team is caught.
98. As the administrator, I want fixture dates to get the right year from the season (July to December → first year, January to June → second year), because the source list omits the year.
99. As the administrator, I want the source fetched gently (detail pages only for new fixtures or a changed venue, with pauses between requests), so that the bot protection doesn't block the app.
100.    As the administrator, I want the initial import of each team season not announced to the team, so that the WhatsApp group isn't flooded at the start of a season.
101.    As the administrator, I want an import that finds nothing different to record no revisions, so that the change history only holds real changes.

## Implementation Decisions

### Schema

The model replaces section 4 of the original spec. The starter-kit tables (users, sessions, password reset tokens, cache, jobs) stay unchanged.

| Table        | Columns                                                                                                                                                                                                                                                                                                                                                        | Notes                                                                       |
| ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| seasons      | id, name (e.g. "2026/27", unique), is_current, timestamps                                                                                                                                                                                                                                                                                                      | Exactly one season is current; the app enforces this when the flag changes. |
| teams        | id, slug (unique), timestamps                                                                                                                                                                                                                                                                                                                                  | Stable identity of one of our teams; holds the calendar address.            |
| team_seasons | id, team_id, season_id, external_id (federation team ID, unique), source_url, name (nullable until first import), competition_name (nullable until first import), auto_import_enabled (default true), timestamps                                                                                                                                               | unique(team_id, season_id).                                                 |
| venues       | id, external_id (federation arena ID, unique), name (not unique), address (nullable), timestamps                                                                                                                                                                                                                                                               |                                                                             |
| fixtures     | id, team_season_id, external_id (federation match ID), round (nullable), is_home, opponent_name, venue_id (nullable), date (local Europe/Prague), time (nullable = TBD), status (scheduled / postponed / finished / cancelled), is_rescheduled (default false), home_score, away_score (nullable), sequence (default 0), missing_count (default 0), timestamps | unique(team_season_id, external_id); index on (team_season_id, date).       |
| imports      | id, team_season_id, trigger (schedule / manual), status (running / ok / error / aborted), started_at, finished_at (nullable), fixtures_found (nullable), error (nullable text), notified_at (nullable)                                                                                                                                                         | Revision counts come from a count query, not a stored column.               |
| revisions    | id, fixture_id, import_id, field (nullable), old_value (nullable), new_value (nullable), created_at                                                                                                                                                                                                                                                            | One row per changed field; `field = null` means the fixture was added.      |

- Statuses, triggers and the revisable field names are backed PHP enums.
- Opponents are only a name on the fixture. There are no `competitions`, `tracked_teams` or opponent team tables (ADR-0002).
- A fixture between two of our teams exists once per team season, each copy with its own revisions and change summary.
- The calendar event length (55 minutes) is an application constant, not a column.

### Revisions

- Revisable fields: date, time, venue, status, is_rescheduled, home_score, away_score.
- Values are stored as raw strings and formatted only for display:
    - date: ISO date,
    - time: `HH:MM`, or null for TBD,
    - venue: its **name**, not its ID, so the history stays readable,
    - status: the enum value.
- Every revision of a fixture increments its `sequence` once per import, and `sequence` becomes the iCalendar SEQUENCE.
- A fixture that appears after the team season's initial import produces one revision with `field = null`.
- The initial import is the team season's first `ok` import. Its additions are not stored as revisions; the changes page shows it as a collapsed "N zápasů přidáno" entry built from that import's `fixtures_found`.

### Import module

- **Entry point.** A deep module whose interface is "import this team season with this trigger", returning the finished Import. Everything else happens inside: fetching, parsing, applying, recording revisions and sending admin emails.
- **Triggers.**
    - The scheduler runs every three hours and dispatches a queued job for each team season with auto import enabled in the current season.
    - An Artisan command imports one team season, or all eligible ones, and is what the schedule calls.
    - The manual "Synchronizovat" action dispatches the same job with trigger `manual`. A manual import also works for team seasons outside the current season, for example when preparing the next one.
- **Concurrency.** The job is unique per team season (lock), so two imports of one team season never overlap.
- **Fetching.**
    - Uses Laravel's HTTP client with a browser-like User-Agent and a pause between requests.
    - A non-2xx response or a network failure ends the import with status `error`; the reason ("HTTP 403 – požadavek zablokován") goes into `error`.
- **Parsing the fixture list page.** The facts below were verified against the live page in 2026-10:
    - Match rows are `Match` blocks. Each row repeats its date and round in a mobile and a desktop wrapper, so use only the first occurrence.
    - Match external ID: the numeric ID in the `/match/detail/default/{id}` link.
    - Date: "NE, 4. 10." with no year; the year is inferred from the team season's season.
    - Time: HH:MM in `Match-startTime`, where 00:00 means a TBD time. Finished rows show the score in that slot instead and have no time, so the stored time is kept (a fixture first seen as finished gets a null time).
    - Home team is on the left and away team on the right. Each links to `/team/detail/overview/{teamId}`; `is_home` is true when the home team's ID equals the team season's `external_id`.
    - Venue: text in `Match-place`. On finished rows that element is replaced by `Match-status` with "odehráno", which sets the status finished and must not be treated as a venue change. The venue is read only from `Match-place`.
    - Score: home:away in `Match-score`, present only on finished fixtures.
    - Round: "N. kolo".
    - Warning icon: a `Tooltip--warning` element with an `aria-label` like "odložené utkání 17.10.2026". Its presence sets `is_rescheduled`.
    - Competition name: comes from the section header (`Matches-body--sectionHeader`).
    - Team header: "PH A SČ LIGA MUŽŮ 2026/2027" (all caps) gives the season, which is checked against the team season ("2026/27"). The header's team name is all caps too, so `team_seasons.name` comes from our team's link in the fixture rows instead.
    - Opponent names can contain repeated whitespace, so all text is whitespace-normalised.
    - Markup for postponed and cancelled fixtures has not been observed yet. A row counts as scheduled only when it has `Match-place` and no `Match-status`; any other status markup is unknown, leaves the status unchanged and is logged.
- **Abort rules.** These are checked before any data is written.
    - 0 fixtures → `aborted`.
    - Fewer than half of the last `ok` import's `fixtures_found` → `aborted`.
    - The page season differs from the team season's season → `aborted`.
    - The reason goes into `error`, and data stays untouched.
- **Applying.** This runs in a single database transaction.
    - Upsert fixtures by (team_season_id, external_id), diff the revisable fields and write revisions.
    - Set `missing_count` to 0 on fixtures that were seen. Increment it on non-cancelled fixtures of the team season that were not seen; at 2 the status becomes `cancelled` and a revision is written.
    - Update `team_seasons.name` and `competition_name`.
- **Venues.**
    - The match detail page (`/match/detail/default/{id}`) is fetched only when a fixture is new, or when its venue text in the list differs from its stored venue's name (not for finished rows).
    - From the detail page we take the arena ID (`/arena/detail/default/{id}`), the name and the address ("Čáslavská 274<br>Kutná Hora" is stored as "Čáslavská 274, Kutná Hora").
    - The venue is looked up by external ID. If it doesn't exist it is created with the address; if it exists under another name, the name is updated.
    - The initial import of a 24-fixture team season therefore makes about 24 extra paced requests once.
- **Admin emails.**
    - After an `ok` import with revisions, a mail with the change summary and the WhatsApp link goes to the administrators (all users). The initial import doesn't send one.
    - After `error` or `aborted`, a mail with the reason is sent.

### Change summary

- It is generated on demand from an import's revisions and never stored. Edits made in the dialog are not persisted; only `notified_at` is.
- The format follows the original spec, rendered in Czech, with a final line linking to the team's public page:

    ```
    📅 Změny v rozpisu {team season name}
    • NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00
    • ST 25. 11. FBC Kutná Hora B – Las Plantas: přeloženo, nový termín 25. 11. 2026 (původně 17. 10. 2026)

    Kalendář: https://{domain}/t/{slug}
    ```

- "Otevřít WhatsApp" opens `https://wa.me/?text=…` with the URL-encoded text.

### Calendar feed (ICS)

- The public route `/calendar/{slug}.ics` serves the fixtures of the team's team season in the **current** season, finished fixtures included. When the team has no team season in the current season, it serves a valid calendar with no events. An unknown slug returns 404.
- Each event has:
    - a stable UID per fixture row and SEQUENCE = `sequence`, so updates replace the existing event,
    - title "Home – Away",
    - location: the venue name plus address when present,
    - description: competition, "N. kolo", "dohrávka N. kola" for rescheduled fixtures, and a link to the federation's match detail page.
- Event variants:
    - Known time: DTSTART/DTEND with TZID Europe/Prague, 55 minutes long.
    - TBD time: an all-day event with "(čas TBD)" in the title and TRANSP:TRANSPARENT.
    - Finished: the score in the title.
    - Postponed: the title starts with "ODLOŽENO:".
    - Cancelled: STATUS:CANCELLED.

### Public team page

- The public route `/t/{slug}` renders an Inertia page with the current season's team season data: competition and season, team name, calendar URLs for Google, webcal (Apple) and Outlook, and upcoming fixtures grouped by match day.
- The button order by device is decided on the client.
- The empty states are: "Sezona skončila" (no upcoming fixtures), "Pro sezonu X zatím není rozpis" (no team season in the current season) and 404 (unknown slug).

### Admin pages

The admin pages sit behind auth. The starter-kit dashboard redirects to the fixture list.

| Page          | Route       | Purpose                                                                                                                                                                                                      |
| ------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Rozpis zápasů | `/fixtures` | fixture list of the selected team season; the manual import action; while an import runs the page polls until it finishes                                                                                    |
| Změny         | `/changes`  | imports with revisions; the change summary dialog; the "mark as sent" action, which sets `notified_at`                                                                                                       |
| Importy       | `/imports`  | paginated import history; the manual import action. Renamed from "Synchronizace" / `/sync-runs`                                                                                                              |
| Týmy          | `/teams`    | team seasons of the selected season; add a new team or carry one over from a previous season; toggle auto import; team actions. Replaces "Sledované týmy" and the per-team "Přepnout na novou sezonu" action |
| Haly          | `/venues`   | venue list; edit the address                                                                                                                                                                                 |
| Sezony        | `/seasons`  | list seasons, create a season, mark a season as current                                                                                                                                                      |

- The selected season and team are stored in the session. The sidebar header switcher changes them.
- The selected season defaults to the current one. Only team seasons of the selected season can be selected.
- The sidebar count of unsent change summaries counts the selected team season's imports that have revisions, have no `notified_at` and are not the initial import.

## Testing Decisions

- Tests exercise external behaviour only, through the app's public entry points, and fake only at the outbound boundary. This follows the existing style (`Socialite::fake()` in the Google authentication tests, the user-create command test).
- **Seam 1 – import.**
    - Tests run an import the way the app does, through the Artisan command or job for a team season, with `Http::fake()` returning saved HTML snapshots of ceskyflorbal.cz: the fixture list page and a match detail page.
    - Variants of the snapshots cover the scenarios: changed time, TBD → time, venue change, warning icon, a fixture missing once and twice, a fixture reappearing, a new fixture after the initial import, 0 fixtures, fewer than half, a season mismatch, HTTP 403, and an identical second import.
    - Assertions cover fixtures, revisions, the import's status and reason, venues with addresses, and emails (`Mail::fake()`).
    - There are no separate parser unit tests; the parser is covered through these scenarios.
    - The concurrency lock is covered by asserting that the job is unique per team season.
- **Seam 2 – HTTP routes.** Feature tests call the routes, with data set up by factories:
    - the ICS feed: event content, UID/SEQUENCE, the all-day TBD event, 55 minutes, cancelled, postponed, the venue address in the location, an empty calendar outside the current season, 404,
    - the public page props and its empty states,
    - each admin page's Inertia props: grouping, badges, the 7-day "Změněno" window, win/loss/draw, the warning after a failed import, empty states, the sidebar count,
    - the actions: manual import dispatch, mark as sent, add a team (new or carried over), toggle auto import, create a season, mark a season as current, edit a venue address, switch season and team,
    - access control: admin routes require auth, public routes don't, registration is disabled.
- **Seam 3 – time.** Tests use `travelTo()` for the 7-day window, "upcoming", import duration and year inference.
- **Browser tests.** These use pest-plugin-browser, following `tests/Browser/Auth/LoginTest.php`, and run only where behaviour lives in the client:
    - Public page: button order on an emulated iPhone vs. Android/desktop; QR code visible only on a wide screen; "Zobrazit celou sezonu" expanding beyond four match days; the instruction tabs; copying the address; no horizontal scroll and no JavaScript errors on mobile.
    - WhatsApp dialog: edit the text → "Otevřít WhatsApp" carries the edited text in the wa.me link → "Označit jako odesláno" appears → after marking, the dialog closes and the sidebar count drops.
- Responsive layouts, dark mode, polling and plain forms are not browser-tested; feature tests cover their server side.
- New tests must satisfy the existing architecture tests: final, readonly classes outside the framework-extending namespaces, strict types, documented methods and properties, and validation through form requests.

## Out of Scope

- Player accounts or registration.
- Automatic sending to WhatsApp. The official API can't post to groups, and unofficial gateways risk getting the number banned.
- A shared club calendar combining several teams.
- Standings, statistics, rosters.
- Tracking opponents across seasons, or storing opponents beyond a name.
- Storing the original date of a rescheduled fixture.
- Fetching every fixture's detail page on every import.
- Auditing admin edits (venue addresses).
- Persisting the edited change summary text.

## Further Notes

- **Dependencies.** Not yet approved; adding any package needs the owner's approval.
    - spatie/laravel-activitylog is deliberately not used (ADR-0001).
    - Generating ICS by hand vs. adding spatie/icalendar-generator is open.
    - A QR code library (or generating the code client-side) is open.
- **Decided while writing this spec, not discussed explicitly.** Revisit if wrong:
    - a dedicated `/seasons` page for creating seasons and marking one current,
    - the Týmy page lists the selected season's team seasons, and carrying a team over replaces the per-team "new season" action,
    - admin emails go to all users,
    - the dashboard redirects to the fixture list.
- **Unknown source markup.** Postponed and cancelled fixtures have not been seen in the source yet. The parser should log unknown status markup so it can be handled once it appears.
- **Open from the original spec:** the domain the app will run on.
