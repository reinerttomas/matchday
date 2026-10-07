# 34 — Importy in the fixture list's row layout

**What to build:** Importy lists one flat, single-line row per import in the same tinted row layout as Rozpis zápasů, so a failed, aborted or revising import stands out at a glance and every row has the same height. Tabs above the list narrow the history to the imports with revisions or to the failed ones.

**Blocked by:** —

**Status:** resolved

- [x] The Table is replaced by a list of rows (a CSS grid, one markup for phone and wide screen, as on Rozpis zápasů).
- [x] On a wide screen (from `xl`, as on Rozpis zápasů, so the row fits next to the expanded sidebar) each row shows, in this order: the date ("7. 10. 2026", muted); the start time ("14:00", emphasized); a trigger icon (calendar-clock for automatic, hand for manual) with a tooltip "Automaticky každé 4 hodiny" / "Spuštěno ručně"; the result badge (OK / Chyba / Přerušeno / Probíhá, the existing `ImportStatusBadge`); the reason of an error or aborted import, otherwise the fixtures found ("22 zápasů", "–" when unknown); the duration ("–" while running); and, when the import has revisions, a violet badge with the revision count ("1 změna", "3 změny", "6 změn") that links to Změny.
- [x] Every row is one line high whatever it shows: the reason replaces the fixtures found in the same column, is cut to one line with an ellipsis, and has no tooltip. Error reasons are rose, aborted reasons amber.
- [x] Rows are tinted like revised fixtures on Rozpis zápasů: an error row rose, an aborted row amber, an ok row with revisions violet; other rows stay plain.
- [x] On a phone each row stays one line: date above time, the trigger icon, the result badge, the reason or fixtures found (cut to one line), and the revision badge. The duration is hidden on a phone.
- [x] Above the list, tabs like Rozpis zápasů's period tabs: "Vše", "Se změnami" (imports with at least one revision) and "Selhané" (error or aborted), each with its count over the whole team season's history. Switching a tab is a GET with a `filter` query parameter (`revised`, `failed`; no parameter means all); an unknown value falls back to all. Pagination keeps the filter, and a page past the end (e.g. an edited address) redirects to the last page with the same filter.
- [x] A filter with no imports shows an empty state ("Žádné importy se změnami" / "Žádné selhané importy"). The existing empty states (no team seasons, no imports yet), the heading, "Synchronizovat" and polling behave as today.
- [x] The presenter returns `startedOn` (date) and `startedAt` (time only) instead of the combined start time, plus server-formatted, correctly pluralized `fixturesFoundLabel` and `revisionsLabel` from `lang/cs/imports.php` (`trans_choice`), and the page gets the selected `filter` and the three tab counts. `triggerLabel` goes away if nothing uses it any more.
- [x] Spec user story 79 (`.scratch/matchday/spec.md`) is updated to the new layout and the filter.
- [x] UI is built from shadcn components (Badge, Tabs, Tooltip, Empty, Pagination, …).
- [x] Feature tests cover the new props: date and time split, the pluralized labels (1, 2–4, 5+ and 0 revisions; fixtures found), each filter including the counts and the fallback for an unknown value, pagination keeping the filter, and the filtered empty case, and the redirect from a page past the end. Existing Importy tests keep passing, adjusted to the new props.
- [x] `composer ci:check` passes.

## Notes

- Decided by a UI prototype on 2026-10-07: four layouts (current table, health overview with a status strip, timeline by day, tinted rows like the fixture list) were compared on `/imports?variant=`; the administrator picked the tinted rows (`D`) and asked that the failure reason not change the row height and not get a tooltip. The prototype lives on branch `prototype/imports-page` (commit 42cfbc7, `resources/js/pages/imports/prototype/variant-d.tsx`). Rewrite it properly; don't copy the prototype code.
- The prototype filtered only the imports on the current page in the browser; that is misleading with 20 per page, so the real filter runs on the server over the whole history.
- The prototype used a ToggleGroup for the filter; use Tabs instead, so it matches the period tabs on Rozpis zápasů.
- Domain texts formatted on the server come from `lang/cs/`; static UI copy is written in Czech in TSX (ticket 16).
