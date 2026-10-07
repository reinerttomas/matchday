# 18 — Admin shell and the Importy page

**What to build:** The administrator gets the real admin area in place of the starter kit's dashboard. A sidebar groups every admin page by purpose. A switcher in its header picks the season and team the administrator works on, and the choice is remembered across pages. The first real page behind it is Importy: the history of the selected team season's imports, so the administrator can see when the fixture list was downloaded and investigate problems. The other pages linked from the sidebar come in tickets 19–26.

**Blocked by:** None — can start immediately

**Status:** resolved

- [x] Prefactor: the status and trigger enums get `label()` methods whose texts come from the matching domain model's file in `lang/cs/` (e.g. import statuses OK / Chyba / Přerušeno / Probíhá), as ticket 12's notes planned for the first admin page that shows a status badge.
- [x] The sidebar has two groups: "Tým" with Rozpis zápasů (`/fixtures`), Změny (`/changes`) and Importy (`/imports`), and "Nastavení" with Týmy (`/teams`), Haly (`/venues`) and Sezony (`/seasons`). Links to pages that don't exist yet may point to their named routes once those tickets land; until then they are left out or disabled, never broken.
- [x] The sidebar footer links to the selected team's public page and to the administrator's account. The starter kit's Repository and Documentation links are gone.
- [x] The sidebar header has a switcher for the season and the team. Changing either sends a POST that stores the choice in the session and returns to the same page.
- [x] The selected season defaults to the current season. Only team seasons of the selected season can be selected; when the season changes, the first team season of that season is selected.
- [x] The selected season, the selected team season and the lists the switcher offers are shared with every admin page as Inertia shared props.
- [x] When the selected season has no team seasons, the team pages (Rozpis zápasů, Změny, Importy) show an empty state "V sezoně X zatím nejsou žádné týmy" with a button to Týmy, and the team switcher is disabled. The Nastavení pages work normally.
- [x] The starter kit's dashboard redirects to the fixture list. Until ticket 19 adds `/fixtures`, it redirects to `/imports`.
- [x] Importy (`/imports`) says "Rozpis se stahuje z ceskyflorbal.cz každé 4 hodiny." The "Synchronizovat" button comes in ticket 20.
- [x] Importy lists the selected team season's imports, newest first, 20 per page, with: start time (plus "ručně" for a manual import), a result badge (OK / Chyba / Přerušeno / Probíhá) with the reason under Chyba and Přerušeno, duration, fixtures found, and the revision count, highlighted when it isn't zero. Revision counts come from a count query, not N+1.
- [x] Every admin route requires login; the public page and the calendar feed stay public.
- [x] Every admin page works on a phone and in light and dark mode.
- [x] UI is built from shadcn components (Sidebar, Select or DropdownMenu, Table, Badge, Pagination, Empty, …).
- [x] Feature tests cover: the default selection, switching season and team (including a team season from another season being rejected), the shared props, the empty state without team seasons, the dashboard redirect, the Importy props (order, pagination, labels, reason, duration, revision count, "ručně"), and that guests are redirected to login.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Admin pages", user stories 34–42, 78 (without the button) and 79.
- Decided with the user: the shell lands together with Importy, the simplest real page, so the switcher is verified on a working page. The selection lives in the session and is changed by a POST action. A season without team seasons shows an empty state linking to Týmy rather than redirecting.
- Domain texts formatted on the server come from `lang/cs/`; static UI copy is written in Czech in TSX (decided in ticket 16).
- The user wants UI built from shadcn components as much as possible. The `@radix-ui/*` packages a shadcn component brings in are approved. The shadcn CLI switches the project to pnpm and adds the umbrella `radix-ui` and `cn` packages: revert that, stay on npm, install the single `@radix-ui/react-*` package and rewrite the generated imports to it and to `cn` from `@/lib/utils`.
    - Superseded by ticket 27: the project now runs on pnpm and imports `cn` from the `cn` package. Ticket 28 covers the umbrella `radix-ui` package.
