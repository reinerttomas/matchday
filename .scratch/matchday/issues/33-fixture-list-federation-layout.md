# 33 — Rozpis zápasů in the federation's row layout

**What to build:** Rozpis zápasů lists one flat row per fixture in the column order of the federation's fixture list on ceskyflorbal.cz (date · round · home · score or time · away · venue), so the administrator can read the two pages side by side line by line. Recent revisions are listed in the row itself instead of a tooltip, and a button opens the team season's fixture list on ceskyflorbal.cz.

**Blocked by:** —

**Status:** resolved

- [x] Fixtures are one chronological list, no longer grouped by month; the month headings and their translation go away.
- [x] On a wide screen each row shows, in this order: day and date ("NE 4. 10."); the round ("4. kolo", empty when the round is unknown); the home team right-aligned; in the middle the score of a finished fixture (emphasized), otherwise the time or a TBD badge; the away team; the venue ("–" when unknown); and the badges. Our team is bold on whichever side it plays.
- [x] The score shows only in the middle column; the result badge ("Výhra", "Prohra", "Remíza") stays among the badges without repeating the score.
- [x] A cancelled fixture has both team names struck through.
- [x] A fixture revised in the last 7 days keeps its "Změněno" badge, gets a subtly highlighted row, and lists its revisions as old → new values under the row, on every screen size. The tooltip goes away.
- [x] On a phone each row stacks: "NE 4. 10. · 4. kolo" on top, then home | score or time | away on one line, then the venue, the badges and the revisions.
- [x] Next to the existing Nadcházející / Celá sezona tabs, an outline button "Otevřít na ceskyflorbal.cz" with an external-link icon opens the team season's fixture list address (`team_seasons.source_url`) in a new tab.
- [x] The presenter returns a flat `fixtures` list instead of `months`, each fixture with `round`, `isHome`, `homeTeam` and `awayTeam` in place of `matchup`, and the fixture list carries `sourceUrl`. Everything else (period, counts, badges, revisions, freshness, failure warning, empty states, Synchronizovat) behaves as today.
- [x] Spec user stories 59, 61 and 67 (`.scratch/matchday/spec.md`) are updated to the new layout.
- [x] UI is built from shadcn components (Badge, Button, Tabs, …); the layout is a CSS grid of rows rather than a Table, so the mobile and wide layouts are one markup.
- [x] Feature tests cover the new props: flat chronological order, round (known and unknown), home and away sides with our team, `sourceUrl`, and that month grouping is gone. Existing badge, revision-window, warning and empty-state tests keep passing, adjusted to the flat list.
- [x] `composer ci:check` passes.

## Notes

- Decided by a UI prototype on 2026-10-07: four layouts (current, federation rows, timeline, calendar) were compared on `/fixtures?variant=`; the administrator picked the federation rows. The prototype lives on branch `prototype/fixture-list-layouts` (commit d9e6f63, `resources/js/pages/fixtures/prototype-variants.tsx`, `VariantB`). Rewrite it properly; don't copy the prototype code.
- The federation's iframe is blocked (`X-Frame-Options: SAMEORIGIN`), so comparing is by opening the source in a new tab.
- Finished fixtures imported after they were played have no venue; that is ticket 32, not this one.
