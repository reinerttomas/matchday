# 04 — Edit a team's category

**What to build:** The "Přejmenovat tým" dialog becomes "Upravit tým" and edits the name and the **category** together; the slug and calendar address never change. Layout per prototype variant A on branch `prototype/team-category-ui` (reference only). See `.scratch/team-category/spec.md` (user stories 18–20, "Update team" decision).

**Blocked by:** 01 — Give every team a category

**Status:** resolved

- [x] The update request and action accept a required `category` alongside the name; the slug stays unchanged.
- [x] The team actions menu item and dialog are titled "Upravit tým"; name and a category `Select` side by side, the unchanged calendar address below.
- [x] The team season list sends the team's category, which the dialog uses as its default.
- [x] Feature tests in the existing rename test: category changed, slug unchanged; invalid category rejected.
- [x] Teams page browser test: editing the category from the actions menu.
- [x] `composer ci:check` passes (stop the Vite dev server first).
