# 45 — Subscription activity page

**What to build:** An admin page that shows what ticket 44 records, so the admin can see how players open the public team page, which subscribe buttons they tap from which browser, and which calendar apps fetch the feed. It is read-only and covers one period at a time.

**Blocked by:** 44 — Record calendar subscription activity

**Status:** needs-triage

- [ ] An authenticated page (e.g. `/subscriptions`, labelled "Odběry" in the sidebar, Czech UI copy in TSX) built from shadcn components.
- [ ] Filters: team (all teams by default) and period (7 / 30 / 90 / 180 days, 30 by default).
- [ ] "Akce na stránce": a table of `TeamPageAction` × `in_app_browser` (null shown as "Prohlížeč") with counts, so e.g. "Google from Messenger" vs. "Google from a browser" can be compared.
- [ ] "Stahování kalendáře": the feed fetch counts grouped by calendar app, plus the top raw User-Agents with their counts. The rules mapping UAs to apps are decided from the data collected by ticket 44.
- [ ] Empty state when the period has no data.
- [ ] Feature tests cover the aggregated props for the filters and the empty state. Guests are redirected to login.
- [ ] `composer ci:check` passes.

## Notes

- Kept at `needs-triage` until ticket 44 has collected a few days of real data. Look at the stored UAs first, then decide how to group them into calendar apps and whether a time chart (before / after ticket 43) is worth it.
