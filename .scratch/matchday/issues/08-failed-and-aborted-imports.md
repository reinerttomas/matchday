# 08 — Failed and aborted imports

**What to build:** A broken download or a broken page can never damage the stored fixture list, and the administrator learns about it. A failed download ends the import as `error`; a suspicious page aborts it before any data is written. Both send the administrators an email with the reason. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module → Fetching, → Abort rules and → Admin emails).

**Blocked by:** 05 — Import a fixture list

**Status:** ready-for-agent

- [ ] A non-2xx response or a network failure ends the import with status `error` and a Czech reason in `error`, e.g. "HTTP 403 – požadavek zablokován". An unreadable page or row (parser exception) also ends as `error` with a reason, instead of leaving the import `running`.
- [ ] The import is `aborted` with the reason in `error` when:
    - the page has 0 fixtures ("Parser vrátil 0 zápasů (minule N)"),
    - it has fewer than half of the last `ok` import's `fixtures_found`,
    - the page's season ("… 2026/2027" in the all-caps team header) differs from the team season's season ("2026/27").
- [ ] On `error` and `aborted` no fixture, venue or team season data changes, and `finished_at` is set.
- [ ] After `error` or `aborted`, an email with the team season and the reason goes to all users.
- [ ] Feature tests run the command with `Http::fake()` for HTTP 403, a connection failure, an empty page, a page with fewer than half, and a season mismatch, asserting status, reason, untouched data and the email (`Mail::fake()`).
- [ ] `composer ci:check` passes.
