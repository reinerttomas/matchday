# 27 — Switch to pnpm and the `cn` package

**What to build:** The project installs its JavaScript dependencies with pnpm instead of npm, and merges Tailwind classes with shadcn's `cn` package instead of `clsx` + `tailwind-merge`. After this, `shadcn add <component>` works as it is: no manual import fixes, no second lockfile, no surprise dependency.

**Blocked by:** None — can start immediately

**Status:** resolved

- [x] pnpm is the only package manager: `pnpm-lock.yaml` replaces `package-lock.json`, and `package.json` pins the version in `packageManager` (`pnpm@12.6.0` is installed locally).
- [x] `pnpm-workspace.yaml` (with its `publicHoistPattern` for `@inertiajs/core`) and `.npmrc` (`ignore-scripts=true`) stay and are honoured. If a dependency needs its build script, allow it explicitly.
- [x] Every npm call moves to pnpm:
    - `composer.json`: `setup` and `ci:check`
    - `.github/workflows/tests.yml`: install pnpm, cache its store, `pnpm exec playwright install --with-deps chromium`. Pin actions by commit SHA like the existing steps.
    - any other place that runs npm or npx (e.g. `php artisan dev`, docs). `grep -rn "npm \|npx " --exclude-dir=node_modules --exclude-dir=vendor` comes back clean apart from generated files.
- [x] Generated guideline files (`AGENTS.md` and other files written by Laravel Boost) mention pnpm instead of npm. Regenerate them with Boost; don't edit them by hand.
- [x] `clsx` and `tailwind-merge` are replaced by `cn` via `pnpm dlx shadcn@latest migrate cn`. `toUrl()` stays in `@/lib/utils`.
- [x] Every component imports `cn` from one place, so components already in the repo and ones the CLI adds later look the same.
- [x] `pnpm dlx shadcn@latest add <component> --dry-run` for a component the repo already has proposes no new dependency and no change to the `cn` import.
- [x] No visual change: the existing browser tests pass, and a quick look at the admin pages and the public team page in light and dark mode shows the same output.
- [x] `composer ci:check` passes, and so does `vendor/bin/pest --parallel --no-tia`. The lockfile change invalidates the TIA graph.

## Notes

- Background from tickets 22 and 24:
    - shadcn CLI 4.21 adds the `cn` package (by shadcn, a drop-in for `clsx` + `tailwind-merge`, at 0.x as of September 2026) to every component it generates, and ignores the `utils` alias in `components.json`.
    - The CLI picks pnpm because `pnpm-workspace.yaml` exists.
    - Running it in an npm project left a stray `pnpm-lock.yaml` and an unexpected dependency. Tickets 22 and 24 wrote Textarea and Switch by hand to avoid that.
- Do the pnpm switch first, then the `cn` migration, so the CLI's migration installs through pnpm.
- pnpm's stricter `node_modules` can surface imports of packages that aren't declared in `package.json`. Declare them rather than adding hoist patterns.
- Deployment (Laravel Cloud) build commands live outside the repo. List what has to change there for the user.
- After this ticket, add shadcn components with `pnpm dlx shadcn@latest add`, not by hand.
- Accepted exceptions, decided with the user:
    - pnpm 12 ignores `ignore-scripts` in `.npmrc`; `ignoreScripts: true` in `pnpm-workspace.yaml` is what applies. `.npmrc` stays only as a guard for anyone who runs npm by mistake.
    - The pest-plugin-agent guideline in `AGENTS.md` and its skill still say `npm install playwright` / `npx playwright install`. That text comes from the vendor package, so it stays.
    - `pnpm import` moved `@radix-ui/react-slot` from 1.3.3 to 1.4.0, a version the lock already had nested.
