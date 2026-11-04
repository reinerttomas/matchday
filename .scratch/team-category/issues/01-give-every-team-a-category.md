# 01 — Give every team a category

**What to build:** A **category** on every team (see `CONTEXT.md`, ADR 0003 and `.scratch/team-category/spec.md`, "Implementation Decisions"): the `Category` enum, a required `teams.category` column, the guess of a category from a competition name, and a migration that fills the category of existing teams from the competition of their latest team season. Nothing in the UI changes yet; the guess is reused by the lookup in ticket 02.

**Blocked by:** none

**Status:** resolved

- [x] A string-backed `Category` enum with the twelve cases and stored values listed in the spec, each with its lowercase Czech label ("ženy").
- [x] A small domain service turns a competition name into a category: category words matched case- and diacritics-insensitively (žen, juniorů, juniorek, dorostenců, dorostenek, starších žáků, starších žákyň, mladších žáků, mladších žákyň, elévů, přípravky …); no category word means men. No AI, no external call.
- [x] Migration: adds `teams.category`, fills it for existing teams from the competition of each team's latest team season (by season order), men when there is none or no category word matches, then makes it non-null. Slugs are not touched.
- [x] `Team` casts `category` to the enum; the factory and seeders set a category (seeders verified via `php artisan migrate:fresh --seed`, no seeder tests).
- [x] Feature test of the migration (new seam): teams with team seasons across several seasons get the category guessed from their latest team season's competition ("1. liga žen" → ženy, "2. liga juniorů - skupina 3" → junioři, "Národní liga" → muži), a team with no competition gets men, slugs unchanged.
- [x] `composer ci:check` passes (stop the Vite dev server first).
