# 28 — Move to the umbrella `radix-ui` package

**What to build:** The shadcn components import Radix from the single `radix-ui` package, as the shadcn CLI now generates them, instead of from separate `@radix-ui/react-*` packages. After this, `pnpm dlx shadcn@latest add <component>` works without touching dependencies or imports for Radix-based components too.

**Blocked by:** 27 — Switch to pnpm and the `cn` package

**Status:** resolved

- [x] `pnpm dlx shadcn@latest migrate radix` rewrites the components' imports to `radix-ui`.
- [x] `package.json` depends on `radix-ui`, and every `@radix-ui/react-*` package nothing imports any more is removed.
- [x] `pnpm dlx shadcn@latest add <component> --dry-run --diff` for Radix-based components the repo already has (e.g. switch, button, dialog) proposes no new dependency and no import change. Report any style changes the diff still shows; don't apply them without asking.
- [x] No visual or behavioural change: the existing browser tests pass, and a quick look at the admin pages and the public team page in light and dark mode shows the same output.
- [x] `composer ci:check` passes, and so does `vendor/bin/pest --parallel --no-tia`.

## Notes

- Found in ticket 27's review: `add switch --dry-run` proposes `+ radix-ui` and rewrites `@radix-ui/react-switch` to `import { Switch as SwitchPrimitive } from "radix-ui"`. `add button` does the same for `@radix-ui/react-slot`.
- This reverses ticket 18's note to install single `@radix-ui/react-*` packages; that rule only existed to keep the shadcn CLI's output out of an npm project.
- Packages that don't come from shadcn (e.g. `@radix-ui/react-*` used directly in app code, if any) follow the same move only when `radix-ui` exports them.
- Accepted exceptions:
    - `radix-ui@1.7.0` brings newer primitives than the single packages had, e.g. dialog 1.1.23 → 1.2.0, navigation-menu 1.2.22 → 1.3.0, tooltip 1.2.16 → 1.3.0, select 2.3.7 → 2.3.8. Screenshots of every admin page, the public team page and the open overlays in light and dark mode are pixel-identical before and after.
    - The style changes the dry-run still shows are not applied here; ticket 29 takes them on.
