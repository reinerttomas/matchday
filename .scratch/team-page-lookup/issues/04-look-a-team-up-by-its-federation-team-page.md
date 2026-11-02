# 04 — Look a team up by its federation team page

**What to build:** An admin-only JSON endpoint that, given the address of any tab of a **federation team page**, downloads the team's fixture list from ceskyflorbal.cz and returns the team's name in its proper spelling, its competition and its season, or tells why it can't. The "Přidat tým" dialog will call it in ticket 05; here it is verified through HTTP. See `.scratch/team-page-lookup/spec.md`.

**Blocked by:**
- 01 — Derive federation team page addresses from the ID and drop `source_url`
- 03 — Parse a fixture list without a stored team season

**Status:** ready-for-agent

- [ ] A GET route under the authenticated admin routes takes the address in the `federation_team_page_url` query parameter. Guests are rejected.
- [ ] A named rate limiter allows 6 lookups a minute per user. Laravel's default 429 response, with its `Retry-After` header, is kept.
- [ ] Whatever tab was pasted, the request goes to the Zápasy address built from the ID.
- [ ] Validation errors (422) are on `federation_team_page_url`:
    - not a federation team page address: the same message as on save;
    - the ID is already used by a team season: the same message as on save, checked before any download;
    - the page header's season differs from the admin's selected season: a message naming both seasons, such as "Stránka patří sezoně 2025/2026, tým ale přidáváte do sezony 2026/2027."
- [ ] When the page cannot be downloaded or parsed (`ConnectionException`, `RequestException` such as a Cloudflare 403, or the parser's `UnexpectedValueException`, which covers a "Just a moment…" challenge page returned with 200), it returns 502 with `{ message }` and logs a warning. A challenge page is never treated as an empty fixture list.
- [ ] Otherwise it returns 200 with `{ externalId, name, competition, season }`:
    - `name`: the team's name from a fixture row, or null when the page lists no fixtures;
    - `competition`: from the page's fixture section header, or null when the page lists no fixtures;
    - `season`: in our season naming ("2026/2027").
- [ ] Without a selected season, it responds like the store request does.
- [ ] Feature test with `Http::fake` and the snapshot of team 45019 (test support helpers), covering:
    - name, competition and season from a Přehled address, a Zápasy address, and an address without scheme, with and without `www.`;
    - the request goes to the Zápasy address whatever tab was pasted;
    - the validation errors for a non-federation address, an ID already used, and a season mismatch;
    - 502 when the fake returns an error status, a connection failure, or a challenge page;
    - null name and competition when the page lists no fixtures;
    - guests rejected;
    - the rate limit enforced.
- [ ] `composer ci:check` passes (stop the Vite dev server first).
