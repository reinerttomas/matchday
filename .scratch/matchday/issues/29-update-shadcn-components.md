# 29 — Update the shadcn components to their current upstream versions

**What to build:** Every shadcn component in `resources/js/components/ui/` is the one `pnpm dlx shadcn@latest add <component>` generates today, instead of the older copies that came with the Laravel React starter kit or were written by hand before ticket 27. A deviation from upstream stays only when it is deliberate and listed in this ticket. After this, adding or updating a component with the CLI shows only our deliberate deviations in its diff.

**Blocked by:** 28 — Move to the umbrella `radix-ui` package

**Status:** resolved

- [x] Each shadcn component in `components/ui` is regenerated with `pnpm dlx shadcn@latest add <component> --overwrite`: alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, empty, input, input-otp, label, navigation-menu, pagination, select, separator, sheet, sidebar, skeleton, sonner, spinner, switch, table, tabs, textarea, toggle, toggle-group, tooltip. `icon.tsx` and `placeholder-pattern.tsx` are not shadcn components and stay.
- [x] Afterwards `pnpm dlx shadcn@latest add <component> --dry-run --diff` shows no difference for any of them, except the deliberate deviations listed under Notes.
- [x] Deliberate deviations are kept and re-applied after the overwrite:
    - Pagination keeps its Czech `aria-label` and `sr-only` texts (and the `asChild` support on `PaginationLink`, if the Importy page still needs it).
    - The sidebar trigger's sr-only text and the rail's `aria-label`/`title` read "Přepnout postranní menu" instead of "Toggle Sidebar".
    - `hooks/use-mobile.ts` stays ours (`useSyncExternalStore`, right value on the first render, SSR-safe). `add sidebar` shows it as an overwrite; don't take it. It was renamed from `.tsx` so the CLI can't create a `.ts` file next to it that would silently shadow it.
    - `sheet.tsx` has no `"use client"` line. `add sheet` generates none, `add sidebar` generates one; the directive does nothing in this app.
- [x] Call sites follow the new component APIs, e.g. Dialog's `showCloseButton`, Button's `size`/`variant` defaults, Select's default `position`. `tsc --noEmit` passes, and the app keeps the behaviour its tests describe.
- [x] The CSS variables the sidebar brings into `resources/css/app.css` are merged with the existing theme, so light and dark mode still use the app's colours.
- [x] The sidebar still works: the collapse toggle, the mobile sheet, tooltips when collapsed, and the badge next to Změny.
- [x] Screenshots of the admin pages (Importy, Rozpis zápasů, Změny, Sezony, Týmy, Haly), the public team page, the login page and the settings pages, in light and dark mode, before and after, are shown to the user with a list of the visible changes. The user accepts them before the ticket is committed.
- [x] `composer ci:check` passes, and so does `vendor/bin/pest --parallel --no-tia` (the browser tests run with `--no-tia` after `pnpm run build`).

## Notes

- Found in ticket 28's dry-run (`shadcn add <component> --dry-run --diff`). Visible changes the upstream versions bring, accepted up front:
    - Dialog and sheet overlay `bg-black/50` instead of `/80`; a `showCloseButton` prop, and for dialog an optional close button in the footer.
    - Tooltip in `bg-foreground`/`text-background` instead of the primary colours, `sideOffset` 0 instead of 4.
    - Select opens `item-aligned` over its trigger instead of as a popper below it; the starter kit's `side`/`sideOffset`/`avoidCollisions={false}` go away.
    - Button without `shadow-xs`, new dark-mode classes, new sizes `xs`, `icon-xs`, `icon-sm`, `icon-lg`.
    - Badge `rounded-full` instead of `rounded-md`, new `ghost` and `link` variants.
    - Smaller changes: checkbox indicator layout, label `flex items-center gap-2`, separator `data-slot="separator"`, focus-visible tweaks in dropdown-menu, navigation-menu and toggle, new `size` props on switch and avatar, `spacing` on toggle-group.
- Where the components came from: most arrived with the starter kit in 516b74e and lag behind upstream. Pagination and table (ticket 18), empty and tabs (ticket 17), textarea (ticket 22) and switch (ticket 24) were added before ticket 27, partly by hand, so they may differ from upstream too.
- `add sidebar` also overwrites `button`, `separator`, `sheet`, `tooltip`, `input` and `skeleton`, and adds 16 CSS variables to `app.css`. Regenerate the sidebar first, then the remaining components, so nothing is overwritten twice with different results.
- The "local" side of the CLI's diff shows some classes run together (e.g. `border-ringfocus-visible`). The files don't contain them; it is a display artefact of the CLI.
- If an upstream change breaks a page in a way that isn't just a new look (e.g. Select's `item-aligned` position clipping inside the sidebar), stop and ask the user instead of patching the component.
- Decided with the user during implementation:
    - Sonner is taken from upstream, so `next-themes` is a new dependency. The app's appearance now lives in next-themes: `AppearanceProvider` in `hooks/use-appearance.tsx` wraps its `ThemeProvider` with the old localStorage key `appearance` and still writes the `appearance` cookie that `HandleAppearance` reads for the first paint. Flash toasts and the bottom-right position moved to `components/app-toaster.tsx`, so `sonner.tsx` stays identical to upstream. `tests/Browser/AppearanceSettingsTest.php` covers the switch.
    - Upstream Alert writes destructive text in `text-destructive`, which the starter kit's dark `--destructive` made unreadable (about 2:1). `.dark --destructive` is now upstream's `oklch(0.704 0.191 22.216)`, and the unused `--destructive-foreground` is gone. Destructive buttons and badges are a lighter red in dark mode.
- Never apply the HSL `--sidebar-*` block that `add sidebar` proposes for `app.css`; the app's sidebar colours are already in the theme.
- Call-site changes the new components needed: `flex` on the two CardHeaders on Změny (upstream CardHeader is a grid), `max-w-sm` on the revisions tooltip on Rozpis zápasů (upstream Tooltip dropped it), `aria-pressed` on the appearance tabs (for the test).
- In dev without the SSR server, React may log "Encountered a script tag…" once for the next-themes inline script. A known next-themes 0.4 issue; production builds and SSR are unaffected.
