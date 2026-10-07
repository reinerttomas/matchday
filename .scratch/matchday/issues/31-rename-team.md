# 31 — Rename a team

**What to build:** The administrator renames a team from the Týmy page. The new name shows everywhere the team is named: the team list, the public page, the calendar, change summaries and notifications. The slug, and with it the calendar address, stays the same, so players stay subscribed.

**Blocked by:** 30 — Name a new team; derive its slug from the name

**Status:** resolved

- [x] Each row's actions menu on Týmy has "Přejmenovat tým", which opens a dialog with the name pre-filled.
- [x] Saving changes only `teams.name`, through a form request and an `UpdateTeam` action (`.ai/rules/actions.md`). The slug never changes; the dialog says that the calendar address stays the same.
- [x] Validation errors on `name`: required, string, max 100 (the same rules and Czech messages as when adding a team, shared rather than copied). No uniqueness check on the name itself.
- [x] Success returns to Týmy with a toast naming the team by its new name.
- [x] The rename applies to the team in every season, because the name belongs to the team rather than to a team season.
- [x] UI is built from shadcn components (DropdownMenuItem, Dialog, Input, …).
- [x] Feature tests cover: renaming (name changed, slug unchanged), the new name in the team list and the calendar feed, each validation failure, and login required. The Týmy page browser test covers the rename dialog.
- [x] `composer ci:check` passes.

## Notes

- Route: a team-level route (e.g. `PATCH teams/{team}/name` or a `TeamController@update`) that doesn't clash with the existing `PATCH teams/{teamSeason}` for auto import. Bind the team by ID.
- `team_seasons.name` stays untouched (see 30).
