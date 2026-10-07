# 35 — Změny in the fixture list's row layout

**What to build:** Změny lists each import with revisions as a thin header row followed by one tinted single-line row per revised fixture, in the same row layout as Rozpis zápasů and Importy, so what still has to go to the team stands out and the page reads like the rest of the admin. Tabs above the list narrow it to the imports still to send.

**Blocked by:** —

**Status:** resolved

- [x] The Cards are replaced by one bordered list (a CSS grid of rows, one markup for phone and wide screen, as on Rozpis zápasů and Importy).
- [x] Each import with revisions starts with a muted header row: the date ("7. 10. 2026", muted) and the start time ("10:00", emphasized), a violet badge with the revision count ("1 změna", "3 změny", "5 změn"), and at the right end either "Poslat do WhatsAppu" (the existing dialog, ticket 22) or the emerald "Odesláno týmu {kdy}" badge.
- [x] Under the header, one row per revised fixture: the day ("NE 18. 10."), "Home – Away" with our team bold, and the fixture's revisions side by side on the same line ("Čas: ~~TBD~~ → 9:00 Hala: – → Sportovní hala Kutná Hora"); a new fixture shows the "Nový zápas v rozpisu" badge as today.
- [x] Fixture rows of an import not yet sent are tinted violet like revised fixtures on Rozpis zápasů; rows of a sent import stay plain.
- [x] On a phone the header shows the date above the time, the badge, and the button shortened to "Poslat" (the sent badge to "Odesláno"); a fixture row shows the day and sides on one line and the revisions wrapped below in smaller text.
- [x] Above the list, tabs like Rozpis zápasů's period tabs: "K odeslání" (imports with no `notified_at`) and "Vše", each with its count. Switching is a GET with a `filter` query parameter (`unsent`, `all`); without it the page opens on "K odeslání" when there is something to send, otherwise on "Vše"; an unknown value falls back to that default.
- [x] The initial import is shown only on "Vše", as the last row of the list in the same header style ("20. 9. 2026 10:00 · 22 zápasů přidáno do rozpisu · První import, týmu se neoznamuje"), collapsed; expanding it lists its fixtures as rows (day, sides). It is never offered for sending.
- [x] "K odeslání" with nothing to send shows an empty state ("Vše je odesláno", the team knows about every change). The existing empty states (no team seasons, "Rozpis se od importu nezměnil", not imported yet) behave as today.
- [x] Sending, marking as sent and the sidebar count behave as today; after marking an import as sent on "K odeslání" it leaves that tab.
- [x] The presenter returns `startedOn` (date) and `startedAt` (time only) for each revising import and the initial import, plus a server-formatted, pluralized `revisionsLabel` reusing `imports.history.revisions` from `lang/cs/imports.php`, and the page gets the selected `filter` and the two tab counts.
- [x] Spec user stories 68–72 and 77 (`.scratch/matchday/spec.md`) are updated to the new layout and the filter.
- [x] UI is built from shadcn components (Badge, Tabs, Collapsible, Button, Empty, …).
- [x] Feature tests cover the new props: date and time split, the pluralized revision labels, each filter with its counts, the default filter with and without something to send, the fallback for an unknown value, the initial import only on "Vše", and the empty "K odeslání". Existing Změny and change summary tests keep passing, adjusted to the new props; the browser test for the WhatsApp dialog keeps passing.
- [x] `composer ci:check` passes.

## Notes

- Decided by a UI prototype on 2026-10-07: four layouts (current cards, an inbox with the WhatsApp text inline, tinted rows like the fixture list, grouped by fixture) were compared on `/changes?variant=` with in-memory demo data; the administrator picked the tinted rows (`C`). The prototype lives on branch `prototype/changes-page` (commit a007e81, `resources/js/pages/changes/prototype/variant-c.tsx`). Rewrite it properly; don't copy the prototype code.
- The prototype filtered in the browser and split the combined start time on the client; the real page gets the filter and the split date and time from the server, as Importy does (ticket 34).
- Domain texts formatted on the server come from `lang/cs/`; static UI copy is written in Czech in TSX (ticket 16).
- Spec: "Admin pages" (Změny), user stories 68–77.
