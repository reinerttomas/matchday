# 01 — Keep every administrator subscribed to every team

**What to build:** The data behind **notification subscriptions** and **import failure notifications**, and keeping it complete: the `notification_subscriptions` table (user × team, unique, cascading, nullable `unsubscribed_at`), the `notify_import_failures` boolean on users (default `true`), the `NotificationSubscription` model with relationships from `User` and `Team` and a way to query a team's subscribed administrators, the idempotent queued job that subscribes every administrator to every team lacking a row, its dispatch after commit from the user creation action (`user:create`) and from creating a brand-new team with its first team season (not from carrying a team over), the migration backfill, and the seeder. Nothing about sending or the settings page yet. See `.scratch/team-notification-preferences/spec.md`, "Schema", "Rows always exist for every pair", "Models".

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] A new administrator created via `user:create` is subscribed to every existing team.
- [x] A new team created via `POST teams` is subscribed by every existing administrator.
- [x] Carrying a team over to a new season keeps existing unsubscriptions (an unsubscribed row stays unsubscribed).
- [x] The migration backfills rows for existing users × teams directly; `migrate:fresh --seed` leaves the seeded administrator subscribed to the seeded teams (verify manually, no seeder/migration tests).
- [x] A factory state or test helper lets existing tests give users notification subscriptions.
- [x] Tests exercise external behaviour only (prior art: `CreateUserCommandTest`, `AddTeamToSeasonTest`); no direct job or model unit tests.
- [x] `composer ci:check` passes.
