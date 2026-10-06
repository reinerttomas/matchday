# Fixtures belong to a team season; opponents are not stored as teams

The original spec stored every team from the source (including opponents) in a `teams` table and each fixture exactly once, referencing home and away teams. We instead make a fixture belong to one team season (`fixtures.team_season_id`, `is_home`, `opponent_name`), and a fixture between two of our teams is stored twice.

The federation (ceskyflorbal.cz) assigns every team a new ID each season and gives nothing that links one season's ID to the next. Names are not unique either (the men's and women's B teams share a name), so opponents cannot be tracked across seasons. We only need an opponent's name. Only our own teams need a stable identity (the calendar slug), and the administrator supplies the link by adding a team to a new season. With shared fixtures, a change detected by one team's import would never show up as a revision for the other team, so that team would never be told about it. Owning the fixture per team season gives each team its own history and its own change summary.

## Considered Options

- **All teams with home/away foreign keys, fixture stored once** (original spec): rejected because of the notification gap above and because opponent rows carry no information we use.
- **Stable `teams` plus per-season rows for opponents too**: rejected because opponents cannot be matched across seasons.

## Consequences

- The schema is `seasons` → `team_seasons` (federation team ID, name, competition, source URL, `auto_import_enabled`) → `fixtures` / `imports` → `revisions`, with `teams` holding only the slug.
- There is no competitions table: the competition is a name on the team season, and the 60-minute calendar event length is a constant.
- Past seasons stay browsable. The calendar and automatic imports use the season the administrator marks as current.
