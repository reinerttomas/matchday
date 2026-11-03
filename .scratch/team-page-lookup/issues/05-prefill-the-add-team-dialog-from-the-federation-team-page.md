# 05 — Prefill the "Přidat tým" dialog from the federation team page

**What to build:** The administrator pastes the address of any tab of a team's **federation team page** into the "Přidat tým" dialog, and the dialog looks the team up (ticket 04): it shows the federation's name, competition, season and ID, prefills the team's name, offers "Nový tým" or "Tým z minulé sezony", and preselects the carry-over team with the same name. If ceskyflorbal.cz can't be reached, a toast says so and the administrator fills the form in by hand. Saving never depends on ceskyflorbal.cz. The layout was chosen from a UI prototype, variant C "Návrh podle svazu", on branch `prototype/add-team-dialog`. That code is a reference, not something to merge. See `.scratch/team-page-lookup/spec.md`, "The dialog flow" and "The dialog layout".

**Blocked by:** 04 — Look a team up by its federation team page

**Status:** resolved

- [ ] The "Nový tým" / "Existující tým" tabs go away. The dialog is one form, and its first field is the address, labelled "Stránka týmu na ceskyflorbal.cz" (`federation_team_page_url`).
- [ ] The lookup is called with Inertia's `useHttp` (no page visit) when an address is pasted or the field loses focus, only when the address changed since the last lookup. A stale response for an earlier address is ignored.
- [ ] While the lookup runs, a skeleton stands in for the team summary.
- [ ] On success, a bordered summary lists Tým, Soutěž, Sezona and ID svazu. The ID links to the Přehled tab in a new tab. A missing name or competition shows "—".
- [ ] Validation errors (422) show on the address field, and the cards don't show.
- [ ] Every other failure shows a Sonner `toast.warning` and counts as "unavailable": no summary, the form continues. A 429 shows "Příliš mnoho dotazů za sebou, zkuste to za chvíli." Any other status, or a network error, shows "Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně."
- [ ] After a success or an "unavailable" lookup, two selectable cards appear: "Nový tým" and "Tým z minulé sezony". The second is disabled when there are no carry-over teams.
    - "Nový tým" shows the name field with the calendar address preview and the note that the calendar address cannot be changed later.
    - "Tým z minulé sezony" shows the carry-over team select and the note that the name and calendar address stay the same.
- [ ] The name is prefilled from the lookup only if the administrator has not typed or edited it, and the calendar address preview follows it. A carried-over team's name is never touched.
- [ ] When the federation's team name equals a carry-over team's name (ignoring case and surrounding or repeated whitespace), the dialog preselects "Tým z minulé sezony" with that team, titles the card "Pokračuje {team}" and explains "Stejný název jako tým z minulé sezony". The administrator can still switch. No prefix or fuzzy matching.
- [ ] "Přidat tým" stays disabled until the lookup has succeeded or come back "unavailable". Saving posts as before: `team_id` for a carry-over team, `name` for a new team, plus `federation_team_page_url`. Server-side validation errors still show on their fields.
- [ ] Built from the existing shadcn components. No prototype switcher or prototype code lands.
- [ ] Teams page browser test, with ceskyflorbal.cz faked:
    - pasting a Přehled address shows the summary and prefills the name, and saving adds a new team;
    - a name typed before the lookup is kept;
    - a carry-over team with the same name is preselected, and saving adds it to the season;
    - an address of an ID already used shows the error on the field;
    - with the fake failing, a toast shows and the form can still be filled in and saved by hand;
    - no JavaScript errors.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
