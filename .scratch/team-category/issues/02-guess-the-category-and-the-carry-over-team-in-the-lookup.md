# 02 — Guess the category and the carry-over team in the team lookup

**What to build:** The team lookup endpoint behind the "Přidat tým" dialog also returns the guessed **category** and the team from the previous season to carry over, so the dialog no longer matches names itself. See `.scratch/team-category/spec.md` ("Lookup endpoint" decision, user stories 3–5 and 24–28).

**Blocked by:** 01 — Give every team a category

**Status:** resolved

- [x] The response gains `category` (value and label of the guess from the competition; null when the competition is unknown) and `carryOverTeamId`.
- [x] `carryOverTeamId` is the team, not yet in the selected season, whose latest team season has the same federation name (normalised: case, surrounding and repeated whitespace) and the same category; null when zero or several teams match. It compares the federation name stored on the team season, not the team's own name.
- [x] An optional `category` query parameter replaces the guess for the matching (validated against the enum), so the dialog can ask again after the administrator changes the category.
- [x] Feature tests in the existing lookup test, ceskyflorbal.cz faked: guesses for "1. liga žen", "2. liga juniorů - skupina 3", "Národní liga"; the women's team picked between same-named men's and women's teams; null for two same-named teams with the same category; a renamed team still found; the category parameter overriding the guess; invalid category rejected.
- [x] `composer ci:check` passes (stop the Vite dev server first).
