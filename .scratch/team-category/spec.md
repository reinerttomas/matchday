Status: ready-for-agent

# Tell same-named teams apart by their category

## Problem Statement

The federation gives several of our teams the same name and tells them apart only by competition. The men's team (https://www.ceskyflorbal.cz/team/detail/overview/45016, Národní liga) and the women's team (https://www.ceskyflorbal.cz/team/detail/overview/45024, 1. liga žen) are both "FBC VHST Kutná Hora". The juniors (https://www.ceskyflorbal.cz/team/detail/overview/45025, 2. liga juniorů – skupina 3) are "FBC Kutná Hora", the same as a senior team could be.

When the administrator adds the second of two same-named teams, the "Přidat tým" dialog prefills the federation's name. The slug derived from it is already taken, so the team cannot be saved without the administrator inventing a different name. Even when the names are made to differ, nothing in the app says which team is the women's and which the men's. Someone who subscribes to both calendars sees two calendars with the same name. In the next season, the dialog preselects the team from the previous season by comparing the federation's name with our team's name. With two same-named teams it can pick the wrong one, and with a renamed team it picks none.

## Solution

A team gets a **category**: the age and gender group it plays in (men, women, juniors, …). The category belongs to the team and holds across seasons, while the competition can change from season to season. A team is told apart by its name and category together.

- The "Přidat tým" dialog guesses the category from the competition on the federation team page, and the administrator sees the guess and can change it. The name stays prefilled exactly as the federation writes it.
- A new team's slug, and with it its calendar address, comes from the name and the category (`fbc-vhst-kutna-hora-zeny`). Teams that already exist keep their slug (ADR 0003).
- The admin shows the category as a badge next to the team's name wherever teams are listed or picked.
- The calendar is named "<name> (<category>)", so two subscribed calendars can be told apart. Event titles and change summaries keep naming the team without its category.
- In the next season, the dialog preselects the team from the previous season whose latest team season has the same federation name and the same category, and only when exactly one team matches.
- The administrator can correct the category when editing the team. The slug never changes.
- Existing teams get their category guessed from the competition of their latest team season, or men when the guess finds nothing.

## User Stories

1. As an administrator, I want every team to have a category, so that two teams with the same federation name can be told apart.
2. As an administrator, I want the category to be one of a fixed list (muži, ženy, junioři, juniorky, dorostenci, dorostenky, starší žáci, starší žákyně, mladší žáci, mladší žákyně, elévové, přípravka), so that categories are spelled consistently.
3. As an administrator, I want the "Přidat tým" dialog to guess the category from the competition on the federation team page, so that I usually don't have to pick it.
4. As an administrator, I want "1. liga žen" guessed as women and "2. liga juniorů – skupina 3" as juniors, so that the guess follows the competition's wording.
5. As an administrator, I want a competition without any category word (such as "Národní liga" or "Superliga") guessed as men, so that men's teams need no correction.
6. As an administrator, I want to see the guessed category in the dialog and change it before saving, so that a wrong guess never ends up on the team.
7. As an administrator, I want to pick the category by hand when ceskyflorbal.cz does not answer, so that I can still add the team.
8. As an administrator, I want the category to be required when adding a new team, so that no team is left without one.
9. As an administrator, I want the team's name prefilled exactly as the federation writes it, without the category, so that the name matches what I see on ceskyflorbal.cz.
10. As an administrator, I want to add the women's "FBC VHST Kutná Hora" after the men's "FBC VHST Kutná Hora", so that both teams can be in the app.
11. As an administrator, I want a new team's calendar address to contain its category (`…/fbc-vhst-kutna-hora-zeny`), so that the address says which team it is.
12. As an administrator, I want the dialog's calendar address preview to follow the name and the category, so that I see the address before saving.
13. As an administrator, I want an error when another team already has the address made from the same name and category, so that two teams never share a calendar address.
14. As an administrator, I want no `-2` suffix added to a taken address, so that a calendar address always says which team it belongs to.
15. As an administrator, I want existing teams to keep their calendar address, so that players already subscribed are not cut off.
16. As an administrator, I want existing teams to get a category guessed from the competition of their latest team season, so that I don't have to fill it in for every team.
17. As an administrator, I want an existing team whose competition has no category word to get men, so that every team has a category after the upgrade.
18. As an administrator, I want to change a team's category when editing the team, so that I can fix a wrong guess.
19. As an administrator, I want changing the category to keep the calendar address, so that subscriptions survive the correction.
20. As an administrator, I want renaming a team to keep working as today, so that the category does not get in the way.
21. As an administrator, I want to see the category as a badge next to the team's name in the team overview, so that I can tell same-named teams apart at a glance.
22. As an administrator, I want to see the category next to each team in the "tým z minulé sezony" picker, so that I carry over the right team.
23. As an administrator, I want to see the category next to each team in my notification settings, so that I subscribe to the right team's change summaries.
24. As an administrator, I want the dialog to preselect the team from the previous season whose federation name and category both match the looked-up team, so that the women's team carries over to the women's team.
25. As an administrator, I want the preselection to compare against the name the federation gave the team last season, not against the name I chose, so that a renamed team is still found.
26. As an administrator, I want no team preselected when two teams from the previous season match, so that the dialog never guesses between them.
27. As an administrator, I want no team preselected when no team matches, so that a new team is offered.
28. As an administrator, I want the preselection to follow a category I change in the dialog, so that correcting the category corrects the carry-over team too.
29. As an administrator, I want a carry-over team chosen by hand to stay chosen when the category changes, so that my choice is never overwritten.
30. As an administrator, I want a carried-over team to keep its name, category, slug and calendar address, so that nothing changes for its players.
31. As a player, I want the calendar in my calendar app to be named "FBC VHST Kutná Hora (ženy)", so that I can tell it apart from the men's calendar I also subscribe to.
32. As a player, I want the "Přidat do Outlooku" link to name the calendar with its category, so that Outlook shows the same name.
33. As a player, I want event titles to stay "FBC VHST Kutná Hora – FA Č. Budějovice", so that titles stay short; the calendar tells me which team it is.
34. As a team member, I want the WhatsApp change summary to keep naming the team as today, so that the message does not change.

## Implementation Decisions

- **Category** is a new string-backed PHP enum with the twelve cases from user story 2. Its stored values are the slugs of the Czech labels (`muzi`, `zeny`, `juniori`, `juniorky`, `dorostenci`, `dorostenky`, `starsi-zaci`, `starsi-zakyne`, `mladsi-zaci`, `mladsi-zakyne`, `elevove`, `pripravka`). Each case has a lowercase Czech label ("ženy"), used for the badge and the calendar name; the slug of a new team is `slug(name + " " + label)`.
- **Schema**: `teams` gets a required `category` column. The migration adds it, fills it for existing teams from the competition of each team's latest team season (by season order) using the same guess as the dialog, falls back to men, and only then makes it non-null. Existing slugs are not touched.
- **Category guess** is a small domain service (a deep module with one entry point: competition name → category). It matches category words in the competition name, case- and diacritics-insensitive, covering the Czech inflected forms that appear in competition names (žen, juniorů, juniorek, dorostenců, dorostenek, starších žáků, starších žákyň, mladších žáků, mladších žákyň, elévů, přípravky). No category word means men. The migration and the lookup both use it. No AI or external service.
- **Lookup endpoint** (the one behind the dialog's federation team lookup): the response gains `category` (the guess, null when the competition is unknown) and `carryOverTeamId` (the team from the previous season whose latest team season has the same federation name, normalised as today, and the same category, or null when zero or several match). The lookup takes an optional category parameter. When the administrator changes the category in the dialog, the dialog looks up the carry-over team again with that category, so the matching lives only on the server. The React-side same-name matching goes away.
- **Store team season request**: a new team requires `category`. The slug is derived from name and category together, and the taken-slug check uses that slug. The error message names the address. A carried-over team ignores `category` and keeps its own.
- **Update team request/action**: accepts `category` alongside the name. The slug stays unchanged, as with renaming today.
- **Team model**: `calendarName()` returns "<name> (<category label>)". It is what the calendar feed and the Outlook link use. `TeamSeason::displayName()` is unchanged, so event titles, the change summary, flash messages and the import failure email keep the plain name.
- **Presenters** that send teams to the admin UI (team season list, team page, admin selection, carry-over team list, notification settings) send `category` (value and label) separately from the name. The UI renders a shadcn `Badge`.
- **Look (validated by UI prototype, variant A, branch `prototype/team-category-ui`)**: in the team table the category is an outline `Badge` right after the team name in the "Tým" column; there is no separate category column and no grouping. In the "Přidat tým" dialog the category `Select` sits beside the name field (name wide, category ~12rem, stacked on mobile) with the hint "Kategorie odhadnutá ze soutěže „…“" under the pair. The rename dialog becomes "Upravit tým" with the same name + category pair and the unchanged calendar address below. The carry-over choice card names the matched team with its category badge ("Pokračuje FBC VHST Kutná Hora [ženy]").
- **Dialog**: a category select (shadcn) beside the name field, prefilled from the lookup unless the administrator already picked one by hand, always enabled. The calendar address preview uses name + category. The carry-over choice is preselected from `carryOverTeamId`, following the existing rules for choices made by hand.
- **Glossary and ADR**: `CONTEXT.md` defines **Category** and the updated **Team**. ADR 0003 records the slug decision.

## Testing Decisions

- Tests exercise external behaviour through HTTP endpoints, the calendar feed and the browser. They never assert on internal helpers. The category guess is tested through the lookup endpoint, not in isolation.
- **Lookup feature test** (prior art: the existing team lookup test with faked ceskyflorbal.cz responses): guess per competition wording ("1. liga žen" → women, "2. liga juniorů – skupina 3" → juniors, "Národní liga" → men, the youth forms), `carryOverTeamId` for a unique match, null for two same-named teams with the same category, the right team picked between same-named teams of different categories, matching on the previous season's federation name for a renamed team, and the category parameter overriding the guess.
- **Add team to season feature test** (prior art: the existing add-team test): category required for a new team, slug made of name and category, a taken slug rejected, the women's team added next to a same-named men's team, a carried-over team keeping its category and slug.
- **Rename team feature test** (prior art: the existing rename test): category changed, slug unchanged.
- **Calendar feed feature test** (prior art: the existing calendar feed test): calendar name contains the category, event titles do not.
- **Migration feature test** (new seam): with teams and team seasons from several seasons, running the migration gives each team the category guessed from its latest team season's competition, or men; slugs unchanged.
- **Browser test** of the teams page (prior art: the existing teams page browser test with the add-team dialog): the category select prefilled from the lookup, the carry-over team preselected by name and category and following a changed category, and the badge shown in the overview.
- No seeder tests. Seeders get a category per team and are verified via `migrate:fresh --seed`.

## Out of Scope

- Renaming existing teams or changing their slugs and calendar addresses.
- Showing the category in event titles, the WhatsApp change summary or the import failure email.
- Using AI or any external service to guess the category.
- Treating opponents by category; opponents remain names only (ADR 0002).
- Changing a team's slug after creation for any reason.

## Further Notes

- Decided in a grilling session on 2026-11-03; see ADR 0003 (team slug includes category) and the **Category** and **Team** entries in `CONTEXT.md`.
- Competition names come from the fixture list page already parsed by the lookup; no new scraping is needed.

## Comments

**2026-11-03 – UI prototype verdict.** Question: how should the category look on /teams (table, "Přidat tým", "Upravit tým")? Three variants were prototyped: A (badge next to the name + select beside the name field), B (own column + chips toggle for all categories), C (table grouped by category + select inside the federation summary card). **Variant A chosen.** The prototype is the primary source and lives on the throwaway branch `prototype/team-category-ui` (commit 3af3cf2, `?variant=A` on /teams); it is not to be merged.
