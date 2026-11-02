Status: ready-for-agent

# Prefill a new team from its federation team page

## Problem Statement

To add a team to a season, the administrator has to find the team on ceskyflorbal.cz, open exactly the Zápasy tab, copy its address, and type the team's name by hand. The tab most often at hand is Přehled (overview), whose address the form rejects. Nothing tells the administrator, before saving, that the address belongs to the wrong team or the wrong season. Before saving, a previous season's address is caught only when its federation team ID is already in use; otherwise the team is saved and only its first import fails with a season mismatch. The administrator also has no quick way from the admin to the team's overview on ceskyflorbal.cz.

## Solution

The address field of the "Přidat tým" dialog accepts the address of any tab of the **federation team page** (Přehled, Zápasy, Soupiska, Statistiky, Kontakty a haly), with or without the scheme. As soon as an address is pasted (or the field loses focus), the app downloads the team's fixture list from ceskyflorbal.cz and does three things:

- It prefills the team's name in its proper spelling, still editable.
- It shows the federation's competition and season read-only, so the administrator can confirm it is the right team.
- It reports straight away when the address belongs to a different season than the one the team is being added to, or when its federation team ID is already used by another team season.

If ceskyflorbal.cz cannot be reached, the administrator sees a warning and fills the form in by hand exactly as today. Saving never depends on ceskyflorbal.cz.

The app no longer stores a fixture list address. Every link to ceskyflorbal.cz (fixture list, overview) is derived from the team season's federation team ID. The admin gains a "Přehled na Český florbal" link next to the existing fixture list link.

## User Stories

1. As an administrator, I want to paste the address of a team's Přehled tab into the "Přidat tým" dialog, so that I don't have to switch to the Zápasy tab first.
2. As an administrator, I want to paste the address of any tab of the federation team page (Zápasy, Soupiska, Statistiky, Kontakty a haly), so that whatever tab I have open works.
3. As an administrator, I want to paste an address without `https://` or with or without `www.`, so that an address copied from the browser's address bar works as it is.
4. As an administrator, I want the team's name prefilled after I paste the address, so that I don't have to type it.
5. As an administrator, I want the prefilled name in its proper spelling ("FBC Kutná Hora B", not "FBC KUTNÁ HORA B"), so that the slug and the calendar address derived from it look right.
6. As an administrator, I want to edit the prefilled name before saving, so that I stay in charge of what the app shows and of the calendar address.
7. As an administrator, I want the prefill not to overwrite a name I have already typed or edited, so that my choice is never lost.
8. As an administrator, I want to see the federation's competition and season for the pasted address, so that I can check it is the team I meant before saving.
9. As an administrator, I want to see a loading state while the federation team page is being downloaded, so that I know the app is working.
10. As an administrator, I want an error as soon as I paste an address that belongs to a different season than the one I am adding the team to, so that I don't add last season's team by mistake.
11. As an administrator, I want an error as soon as I paste an address whose federation team ID is already used by another team season, so that I notice a stale address before saving.
12. As an administrator, I want an error as soon as I paste something that is not a federation team page address, so that I can correct it straight away.
13. As an administrator, I want a warning when ceskyflorbal.cz cannot be reached, and still be able to fill in the form and save it by hand, so that an outage at the federation never blocks me.
14. As an administrator, I want the name left empty when the team has no fixtures yet, so that I don't get a name in capitals that I have to rewrite.
15. As an administrator carrying a team over from a previous season, I want the same address field and the same check of competition, season and ID, so that I see I picked the right new-season page.
16. As an administrator carrying a team over, I want the team's name left untouched, so that its slug and calendar address don't change.
17. As an administrator, I want saving to keep validating the address format and the ID uniqueness on its own, so that the data stays correct even if the lookup was skipped or failed.
18. As an administrator, I want the team's first import queued on save exactly as today, so that the fixture list fills in without extra steps.
19. As an administrator, I want a "Přehled na Český florbal" link next to each team season on the Týmy page, so that I can get to more information about the team quickly.
20. As an administrator, I want the same overview link on the team season's Zápasy page, so that I can reach it from wherever I am working with the team.
21. As an administrator, I want the existing links to the fixture list on ceskyflorbal.cz to keep working, so that nothing I rely on disappears.
22. As a visitor of a team's public page, I want the link to the fixture list on ceskyflorbal.cz to keep working, so that I can check the source.
23. As an administrator, I want the import-failure email to keep linking to the fixture list on ceskyflorbal.cz, so that I can look at the page that failed.
24. As the operator, I want lookups rate-limited, so that the federation's bot protection doesn't block the app's scheduled imports because of repeated pasting.
25. As the operator, I want the lookup available only to signed-in administrators, so that the app cannot be used to make requests to ceskyflorbal.cz on someone else's behalf.

## Implementation Decisions

- **HTML parsing only, no AI.** The federation team page has stable markup and the fixture list parser already reads the team header. A language model would add an API key, cost, latency and non-deterministic output with no benefit.
- **The fixture list is always the page downloaded.** Whatever tab the pasted address points to, the app takes the federation team ID from it and downloads the Zápasy tab built from that ID. Every tab carries the same team header (name in capitals, competition, season), and the Zápasy tab additionally carries the team's name in its proper spelling in each fixture row, which the existing parser already reads. No snapshot of the Přehled tab is needed.
- **One module for federation team page addresses** replaces the current fixture list address module. Its interface:
  - Read the federation team ID from an address of any tab (`/team/detail/{tab}/{id}`, scheme optional, `www.` optional, host must be ceskyflorbal.cz), or return null.
  - Build the fixture list address from an ID.
  - Build the overview address from an ID.
- **The `source_url` column of team seasons is dropped** in a migration. Everything that read it derives the address from `external_id` through the module above: the fixture list download in the client, the import-failed email, and the presenters for the Týmy page, the Zápasy page and the public team page. The props keep their current names for the fixture list address. The two admin presenters gain an overview address. Team season creation no longer takes a source address, and the factory, the demo seeder (`DemoSeeder`) and test support (`Tests\Support\Ceskyflorbal::kutnaHoraTeamSeason()`) stop setting it. Tests that read or fake `source_url` derive the address from the ID instead: `CurrentSeasonTest`, `Commands\ImportFixturesScheduleTest` (it fakes `$teamSeason->source_url`) and the Teams page browser test (it asserts the fixture list link's `href`).
- **The fixture list parser must work without a stored team season.** Today it needs a team season to find the team's own link in a fixture row. It should instead take the federation team ID, so the lookup can parse a team that is not stored yet. The import keeps calling it with the team season's ID.
  - The parser also uses the team season today for the season's start year, which it needs to put a year on fixture dates such as "NE, 4. 10.". It takes that year from the season in the page's team header instead (`parse($html, $externalId)`). The import is not affected: a page whose header shows another season aborts with `season_mismatch` before its fixture dates are used.
  - The warning about an unknown fixture status logs the federation team ID (`external_id`) instead of `team_season_id`.
- **A new admin-only JSON endpoint looks a team up** by address, under the existing authenticated admin routes. It is rate-limited with its own named limiter (a few requests per minute per user) and takes the address as a query parameter. It is called from the dialog with Inertia's `useHttp`, without a page visit. Behaviour:
  - When the address is not a federation team page address, it returns a validation error on the address (same message as on save).
  - When the ID is already used by a team season, it returns a validation error on the address (same message as on save).
  - When the page cannot be downloaded or parsed (Cloudflare challenge, timeout, unexpected markup), it returns the "unavailable" response: status 502 Bad Gateway with `{ message }`, and logs a warning. It catches `ConnectionException`, `RequestException` (a challenge page often comes with a 403) and the parser's `UnexpectedValueException`. 502 follows what other Laravel apps return when a service they depend on fails (for example trypost, coolify, nmrxiv); 503 would claim our own app is down, and 4xx would blame the request.
  - The named limiter allows 6 lookups a minute per user. Laravel's default 429 response, with its `Retry-After` header, is kept.
  - When the season in the page header differs from the season the administrator is adding the team to (the admin's selected season), it returns a validation error on the address naming both seasons.
  - Otherwise it returns `{ externalId, name, competition, season }`. `name` is the team's name from a fixture row, or null when the page lists no fixtures. `competition` comes from the page's fixture section header, or null when the page lists no fixtures (that header belongs to the fixture block; the team header spells the competition in capitals, so it is not used as a fallback). This matches the import, where the competition is nullable too. `season` is in our season naming ("2026/2027").
- **The dialog flow.** The lookup is triggered when an address is pasted or the field loses focus, and only when the address changed since the last lookup. A stale response for an earlier address is ignored. While it is in progress, the field shows a loading state. On success:
  - New team: the name is prefilled only if the administrator has not typed or edited it.
  - Carried-over team: the name is never touched.
  - Both: competition and season are shown read-only under the field.
  - Validation errors (422) show on the address field through `useHttp`'s `errors`.
  - Every other failure shows as a Sonner toast (`toast.warning`), like the app's other notices, and never blocks saving. In `onHttpException`, a 429 shows "Příliš mnoho dotazů za sebou, zkuste to za chvíli.", and any other status shows "Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně." `onNetworkError` shows the second message too. The dialog then treats the lookup as "unavailable" (see the layout below).
- **The dialog layout** (chosen from a UI prototype, variant C "Návrh podle svazu"; the prototype lives on branch `prototype/add-team-dialog`, see `resources/js/components/add-team-dialog.prototype.tsx` there):
  - The "Nový tým" / "Existující tým" tabs go away. The dialog is one form: the address field comes first, labelled "Stránka týmu na ceskyflorbal.cz".
  - While the lookup runs, a skeleton stands in for the team summary.
  - After a successful lookup, a bordered summary lists Tým, Soutěž, Sezona and ID svazu (the ID links to the Přehled tab). A missing name or competition shows "—".
  - When the lookup is "unavailable" (a 429 included), no summary shows; the toast explains why, and the form continues below without it.
  - After the lookup succeeds or comes back "unavailable", two selectable cards appear: "Nový tým" and "Tým z minulé sezony". The second is disabled when there are no carry-over teams. Choosing "Nový tým" shows the name field with the calendar address preview and a one-line note that the calendar address cannot be changed later. Choosing the other shows the carry-over team select and the note that the name and calendar address stay the same.
  - When the federation's team name equals a carry-over team's name (ignoring case and surrounding or repeated whitespace), the dialog preselects "Tým z minulé sezony" with that team, titles the card "Pokračuje {team}" and explains "Stejný název jako tým z minulé sezony". The administrator can still switch to "Nový tým". The match is exact on purpose: a club often has several teams ("FBC Kutná Hora", "FBC Kutná Hora B"), and a prefix match would preselect the wrong one, while a renamed team simply gets no preselection.
  - "Přidat tým" stays disabled until the lookup has succeeded or come back "unavailable", so the administrator always picks new or carry-over with the federation's data in view. On a validation error the cards don't show.
  - The choice is sent as before: `team_id` for a carry-over team, `name` for a new team.
- **Saving makes no network call.** The store request keeps its current validation (address format, now accepting any tab, and ID uniqueness) and stores `external_id` only. The season check is done by the lookup only, so an outage at ceskyflorbal.cz never blocks saving, and the first import validates the page anyway.
- **The address field is named `federation_team_page_url`**, after the glossary term, in the dialog form, the store request, the lookup's query parameter and the key of its validation errors, and in the translation keys (`team_seasons.validation.federation_team_page_url_required`, `…_format`, `…_taken`). It replaces `source_url`, which no longer names anything stored. `team_page_url` is avoided because "team page" already means our public team page. The messages speak of the team's page, not its fixture list, for example "Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019." and "Tato stránka už patří týmu :team_season."
- **Overview link** ("Přehled na Český florbal", Czech translation strings) next to the existing fixture list link on the Týmy page and on the team season's Zápasy page. The public team page is unchanged.
- UI is built from the existing shadcn components.
- The glossary term **federation team page** (in the domain context file) names the page. The glossary's **team season** no longer lists a fixture list address; it says every address of the federation team page is derived from the federation team ID. Avoid "overview" or "team detail" in code names for the whole page. "Overview" is fine for the Přehled tab specifically.

## Testing Decisions

- Tests exercise external behaviour through HTTP: requests to admin routes and the JSON and Inertia props they return. They don't test the address module or the parser on their own, because the feature tests cover both. ceskyflorbal.cz is faked with `Http::fake` using the saved fixture list snapshot of team 45019 and the helpers in the test support class. There are no seeder tests.
- **New: lookup endpoint feature test.** It covers:
  - Prefilled name, competition and season from a Přehled address, a Zápasy address, and an address without scheme or with or without `www.`.
  - The validation error for a non-federation address.
  - The validation error for a season mismatch.
  - The validation error for an ID already used by a team season.
  - The "unavailable" response when the fake returns an error or a challenge page.
  - A null name when the page lists no fixtures.
  - Guests rejected.
  - Rate limit enforced.
  - The request goes to the Zápasy address whatever tab was pasted.
- **Existing: add-team-to-season feature test.** It is extended so that:
  - A Přehled address is accepted on save.
  - Only `external_id` is stored.
  - Existing format and uniqueness errors still apply to any tab's address.
  - Saving makes no request to ceskyflorbal.cz.
- **Existing: Týmy, Zápasy and public team page tests.** They assert the fixture list address derived from the ID. The two admin pages also assert the overview address.
- **Existing: import tests and the import-failed email test** keep passing with the address derived from the ID. This shows that dropping the column didn't break the import.
- Prior art: the add-team-to-season test (form request validation through HTTP), the manual import and import-failed tests (`Http::fake` with the snapshot), and the Týmy page test (Inertia props).
- **Existing: Teams page browser test.** It drives the dialog through its tabs today, so it moves to the single form and the choice cards, with ceskyflorbal.cz faked.
- `composer ci:check` must pass. Stop the Vite dev server first.

## Out of Scope

- Storing anything else from the federation team page (roster, statistics, venues, club, logo).
- Links to other tabs (Soupiska, Statistiky, Kontakty a haly). The module makes them trivial to add later.
- An overview link on the public team page.
- Changing how the first import fills in a team season's imported name and competition.
- Checking the season or downloading the page on save.
- Any use of a language model.

## Further Notes

- The federation assigns a new team ID every season (ADR 0002), so the ID and every derived link belong to the team season, never to the team.
- From a developer machine, plain requests to ceskyflorbal.cz can hit a Cloudflare "Just a moment…" challenge. The lookup must treat that HTML like any other unparseable page ("unavailable"), not as an empty fixture list.
- Dropping `source_url` is easily reversible and needs no ADR.
