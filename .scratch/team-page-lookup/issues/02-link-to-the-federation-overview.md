# 02 — Link to the team's overview on Český florbal

**What to build:** The administrator has no quick way from the admin to the team's Přehled tab on ceskyflorbal.cz. Add a "Přehled na Český florbal" link next to the existing fixture list link for each team season on the Týmy page, and on the team season's Zápasy page. The link is derived from the team season's federation team ID with the address module from ticket 01. The public team page is unchanged. See `.scratch/team-page-lookup/spec.md`.

**Blocked by:** 01 — Derive federation team page addresses from the ID and drop `source_url`

**Status:** ready-for-agent

- [ ] The Týmy page and Zápasy page presenters return an overview address (`https://www.ceskyflorbal.cz/team/detail/overview/{external_id}`) beside the fixture list address. The public team page presenter does not.
- [ ] Both admin pages show "Přehled na Český florbal" next to the existing fixture list link, opening in a new tab, built from the existing shadcn components and matching how the fixture list link is shown there (on the Týmy page that is the row's actions). The label comes from Czech translation strings.
- [ ] Tests:
    - Týmy and Zápasy feature tests assert the overview address in the Inertia props.
    - Public team page test asserts no overview address.
    - The Teams page browser test asserts the link's `href`.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
