# 03 — Add a team with its category

**What to build:** The "Přidat tým" dialog lets the administrator pick the team's **category**, prefilled from the lookup, and a new team's slug is made of its name and category. The look was chosen from a UI prototype, variant A, on branch `prototype/team-category-ui` (reference only, not to merge). See `.scratch/team-category/spec.md` ("Store team season request", "Look", "Dialog" decisions; user stories 6–15, 22, 24–30).

**Blocked by:** 02 — Guess the category and the carry-over team in the team lookup

**Status:** ready-for-agent

- [ ] A new team requires `category`. Its slug is `slug(name + " " + category label)` (`fbc-vhst-kutna-hora-zeny`); a taken slug is rejected with a message naming the address; no suffix is added. A carried-over team ignores `category` and keeps its own.
- [ ] The dialog: a shadcn `Select` "Kategorie" beside the name field (name wide, category ~12rem, stacked on mobile), with "Kategorie odhadnutá ze soutěže „…“" under the pair when guessed. Prefilled from the lookup unless picked by hand; pickable by hand when ceskyflorbal.cz does not answer.
- [ ] The calendar address preview follows name and category.
- [ ] The carry-over choice is preselected from the lookup's `carryOverTeamId`; when the administrator changes the category, the dialog looks the carry-over team up again with the `category` parameter. A choice made by hand is never overwritten. The client-side same-name matching is removed. The carry-over card and the carry-over select show the team's category badge (the carry-over team list sends the category).
- [ ] Feature tests in the existing add-team test: category required; slug from name and category; the women's team added next to a same-named men's team; a taken slug rejected; a carried-over team keeping its category and slug.
- [ ] Teams page browser test, ceskyflorbal.cz faked: the category prefilled from the lookup and saved; the carry-over team preselected by name and category and following a changed category; no JavaScript errors.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
