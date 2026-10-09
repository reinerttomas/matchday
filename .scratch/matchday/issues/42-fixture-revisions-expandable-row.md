# 42 — Show a fixture's revisions in an expandable row

**What to build:** On `/fixtures`, a revised fixture keeps a single-line row. Today its revisions are listed under the row and stretch it by one line per revision, which gets hard to scan when many fixtures changed. Instead, the "Změněno" badge shows how many revisions there are and expands a panel under the row that lists them as field · old value → new value. The panel is collapsed by default. The other badges (Dohrávka, Odloženo, Zrušeno, result badges) stay as they are.

**Status:** resolved

- [x] The fixture list sends each fixture's recent revisions as parts (`FixtureFormatter::revisionParts`, the same shape the `/changes` page gets: `RevisionPart[][]`) instead of finished sentences, so the page can strike through the old value while the wording stays in `lang/cs/revisions.php`. The `FixtureListItem` type follows.
- [x] A revised fixture's "Změněno" badge is a button labelled "Změněno", followed by the number of revisions and a chevron. It keeps the violet badge style, toggles the panel, and sets `aria-expanded`. Each row toggles on its own, and every row starts collapsed.
- [x] The expanded panel spans the full row under its cells and lists one revision per line. The field name is muted in its own column, the old value is struck through, and the new value is emphasised. A fixture that was added after the initial import shows "Nový zápas v rozpisu" on its own line. Long values (hall names) wrap inside the panel.
- [x] A collapsed revised row is exactly as tall as an unrevised one, keeps its violet tint, and on a wide screen the badges column fits "Odloženo"/"Dohrávka"/"Zrušeno" next to "Změněno 3 ⌄" on one line. In the prototype that took widening the column from 10rem to 12rem. On a phone the panel sits under the stacked cells.
- [x] The revision rendering is shared with `/changes` (`Revision` in `resources/js/pages/changes/index.tsx`) where it fits, rather than duplicated.
- [x] `FixtureListPageTest` covers the structured revisions (field change and added fixture). The 7-day window and the badges are unchanged.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-09 with a UI prototype. Five designs were compared on fake data: the current inline list, a dialog opened from the badge, a tooltip on the badge, changed values highlighted in their cells, and an expandable row. The expandable row won. The tooltip doesn't work on touch screens, and the dialog hides the context of the row.
- The prototype is on the branch `prototype/fixtures-data-scenarios` (commit `c27f197`). See `ExpandTrigger` and `ExpandedRevisions` in `resources/js/pages/fixtures/fixtures-prototype-revisions.tsx` for the look. It parses the revision sentences on the client, which is exactly what the structured parts replace. Do not merge the branch. It also holds fake data scenarios (`?variant=`) that are handy for checking the page by hand while running `pnpm dev`.
