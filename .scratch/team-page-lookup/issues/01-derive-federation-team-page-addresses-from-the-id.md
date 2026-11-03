# 01 — Derive federation team page addresses from the ID and drop `source_url`

**What to build:** Today the "Přidat tým" dialog accepts only the address of the Zápasy tab, and every team season stores that address in `source_url`. Instead, the administrator can paste the address of any tab of the **federation team page** (Přehled, Zápasy, Soupiska, Statistiky, Kontakty a haly), with or without `https://` and with or without `www.`, and saving takes only the federation team ID from it. The app no longer stores an address: every link to ceskyflorbal.cz is derived from the team season's `external_id`. Nothing changes in what the administrator or a visitor sees, except that more addresses are accepted and the messages speak of the team's page. See `.scratch/team-page-lookup/spec.md`.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [ ] One module for federation team page addresses replaces the fixture list address module. It reads the federation team ID from an address of any tab (`/team/detail/{tab}/{id}`, scheme optional, `www.` optional, host must be ceskyflorbal.cz) or returns null, builds the fixture list (Zápasy) address from an ID, and builds the overview (Přehled) address from an ID. Avoid "overview" or "team detail" in names for the whole page; "overview" is fine for the Přehled tab.
- [ ] A migration drops `source_url` from team seasons. Like every migration here, it is one-way (no `down()`).
- [ ] Everything that read `source_url` derives the fixture list address from `external_id`: the client's fixture list download, the import-failed email, and the presenters for the Týmy page, the Zápasy page and the public team page. Props keep their current names (`sourceUrl`).
- [ ] The form field, the store request and the translation keys are renamed from `source_url` to `federation_team_page_url` (`team_seasons.validation.federation_team_page_url_required`, `…_format`, `…_taken`). The messages speak of the team's page: "Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019." and "Tato stránka už patří týmu :team_season." The dialog's field label, placeholder and help text say the same: any tab of the team's page works.
- [ ] Team season creation takes only `external_id`. The factory, the demo seeder and the test support's Kutná Hora team season stop setting `source_url`.
- [ ] Saving still validates the address format (any tab) and that the ID is not used by another team season, and still queues the first import. Saving makes no request to ceskyflorbal.cz.
- [ ] The glossary's **team season** in the domain context file already says the addresses are derived from the ID; keep it that way.
- [ ] Tests:
    - Add-team-to-season feature test: a Přehled address is accepted; an address without scheme and with or without `www.` is accepted; only `external_id` is stored; format and uniqueness errors apply to any tab's address under `federation_team_page_url`; `Http::assertNothingSent()` on save.
    - Týmy, Zápasy and public team page tests assert the fixture list address derived from the ID.
    - Import tests, the import schedule test and the import-failed email test pass with the address derived from the ID (tests that faked `$teamSeason->source_url` fake the derived address).
    - The current-season test and the Teams page browser test no longer set or read `source_url`; the browser test fills `federation_team_page_url`.
- [ ] `composer ci:check` passes (stop the Vite dev server first). `migrate:fresh --seed` runs.
