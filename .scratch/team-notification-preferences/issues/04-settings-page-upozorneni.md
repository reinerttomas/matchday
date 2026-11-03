# 04 — Settings page "Upozornění"

**What to build:** A settings page where the signed-in administrator flips a "Chyby importu" switch and one switch per team of the current season, each saved immediately with a success toast. `GET settings/notifications` plus an endpoint for the import-failure switch and one to subscribe/unsubscribe the signed-in administrator to a route-bound team (unsubscribe sets `unsubscribed_at` to now, subscribe clears it; unknown team → 404). Added to the settings navigation next to Profile, Security and Appearance. Layout is variant A from the prototype on branch `prototype/team-notification-preferences` (commit `920b181`) — a reference only, rewrite it properly and merge none of its prototype code. See `.scratch/team-notification-preferences/spec.md`, "Routes and controllers" and "Frontend".

**Blocked by:** 01 — Keep every administrator subscribed to every team

**Status:** resolved

- [x] Guests are redirected to login.
- [x] The page lists only the current season's teams, sorted by name, each with its competition (omitted when unknown) and the correct subscribed flag, plus the import-failure flag.
- [x] Unsubscribing marks the administrator's row unsubscribed; subscribing again clears it; another administrator's row is untouched.
- [x] Toggling the import-failure switch updates only the signed-in administrator.
- [x] Responses redirect back with a success toast, matching existing settings controllers.
- [x] UI built from shadcn `FieldGroup` / `FieldSet` / `FieldLegend` / `Field orientation="horizontal"` / `FieldSeparator` and `Switch`, Wayfinder route functions, Czech texts as in the spec. Use pnpm.
- [x] HTTP feature tests (prior art: `tests/Feature/Settings/ProfileUpdateTest.php`); no browser tests.
- [x] `composer ci:check` passes.
