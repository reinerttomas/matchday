# 30 — Name a new team; derive its slug from the name

**What to build:** When adding a brand-new team, the administrator enters the team's name and the address of its fixture list. The slug, and with it the calendar address, is derived from the name instead of being typed. The name belongs to the team and stays the same across seasons; it's what the app shows everywhere a team is named, and imports don't overwrite it.

**Blocked by:** 25 — Add a team to a season

**Status:** resolved

- [x] `teams` gets a required `name` column, added to the original `create_teams_table` migration (the app isn't deployed yet, so no data migration).
- [x] The "Nový tým" form has two fields: "Název týmu" and "Adresa rozpisu zápasů". The slug field is gone.
- [x] The slug is derived on the server with `Str::slug($name)` (e.g. "FBC Kutná Hora B" → `fbc-kutna-hora-b`); the request never accepts a `slug` input.
- [x] While the name is typed, the form previews the resulting calendar address (a client-side slugify that matches `Str::slug` for Czech diacritics) and keeps the warning that the calendar address won't change later, reworded around the name.
- [x] Validation through `StoreTeamSeasonRequest`, errors on the `name` field: name required, string, max length; a name whose slug is empty is rejected; a name whose slug is already taken is rejected (no automatic suffix). Czech messages in `lang/cs/teams.php`.
- [x] Carrying a team over is unchanged: it keeps its name and slug, and no name is entered.
- [x] `TeamSeason::displayName()` returns the team's name; `Team::calendarName()` returns the team's name. Everything built on them (team list, carry-over list, public page, calendar, change summary, notifications, admin selection) shows the team's name. Eager-load `team` where needed so no N+1 appears.
- [x] The import keeps filling the team season's own `name` from ceskyflorbal.cz, but it no longer decides what the app shows.
- [x] The add dialog's description no longer says the team name comes from ceskyflorbal.cz (only the competition does).
- [x] `TeamFactory` produces a name and a matching slug; `DemoSeeder` sets team names.
- [x] `CONTEXT.md` and the spec ("Schema" teams row, user stories 45–46) describe the team's name.
- [x] Feature tests cover: adding a new team with a name (slug derived, name stored), each name validation failure (missing, too long, empty slug, taken slug), a submitted `slug` being ignored, display names coming from the team's name; browser test of the Týmy page updated for the new form and preview.
- [x] `composer ci:check` passes.

## Notes

- Decided with the user: the name is a permanent team attribute (not per season), the slug is never typed, a slug collision is a validation error.
- `team_seasons.name` stays for now, still filled by imports though nothing displays it; whether to drop it is left for later.
- The working tree has unrelated uncommitted changes (season naming in tests and a few app files); leave them as they are.
