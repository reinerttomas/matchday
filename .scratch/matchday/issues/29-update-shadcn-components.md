# 29 — Update the shadcn components to their current upstream versions

**What to build:** Every shadcn component in `resources/js/components/ui/` is the one `pnpm dlx shadcn@latest add <component>` generates today, instead of the older copies that came with the Laravel React starter kit or were written by hand before ticket 27. A deviation from upstream stays only when it is deliberate and listed in this ticket. After this, adding or updating a component with the CLI shows only our deliberate deviations in its diff.

**Blocked by:** 28 — Move to the umbrella `radix-ui` package

**Status:** ready-for-agent

- [ ] Each shadcn component in `components/ui` is regenerated with `pnpm dlx shadcn@latest add <component> --overwrite`: alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, empty, input, input-otp, label, navigation-menu, pagination, select, separator, sheet, sidebar, skeleton, sonner, spinner, switch, table, tabs, textarea, toggle, toggle-group, tooltip. `icon.tsx` and `placeholder-pattern.tsx` are not shadcn components and stay.
- [ ] Afterwards `pnpm dlx shadcn@latest add <component> --dry-run --diff` shows no difference for any of them, except the deliberate deviations listed under Notes.
- [ ] Deliberate deviations are kept and re-applied after the overwrite:
    - Pagination keeps its Czech `aria-label` and `sr-only` texts (and the `asChild` support on `PaginationLink`, if the Importy page still needs it).
    - Anything else the agent wants to keep is added to the list under Notes with its reason, after asking the user.
- [ ] Call sites follow the new component APIs, e.g. Dialog's `showCloseButton`, Button's `size`/`variant` defaults, Select's default `position`. `tsc --noEmit` passes, and the app keeps the behaviour its tests describe.
- [ ] The CSS variables the sidebar brings into `resources/css/app.css` are merged with the existing theme, so light and dark mode still use the app's colours.
- [ ] The sidebar still works: the collapse toggle, the mobile sheet, tooltips when collapsed, and the badge next to Změny.
- [ ] Screenshots of the admin pages (Importy, Rozpis zápasů, Změny, Sezony, Týmy, Haly), the public team page, the login page and the settings pages, in light and dark mode, before and after, are shown to the user with a list of the visible changes. The user accepts them before the ticket is committed.
- [ ] `composer ci:check` passes, and so does `vendor/bin/pest --parallel --no-tia` (the browser tests run with `--no-tia` after `pnpm run build`).

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
