Status: ready-for-agent

# Team notification preferences

## Problem Statement

Every administrator receives every email the app sends: the change summary of every team after each import that recorded revisions, and every import failure of every team. An administrator who looks after one or two teams is flooded with change summaries of teams they have nothing to do with, and has no way to turn them off. Subscribing to several teams' calendar feeds is not the issue — the calendar feed is anonymous and keeps working — the issue is the emails.

At the same time, import failures must not silently disappear. Today some of them (the federation answering with an HTTP error, an unreadable fixture list, an aborted import) are reported only by email and never reach Nightwatch, so if nobody received the email, nobody would know.

## Solution

A new settings page "Upozornění" where each administrator chooses:

- which teams they want change summaries for (one switch per team of the current season), and
- whether they want import failure notifications at all (one switch for all teams).

Everything is on by default, including teams added later, so nobody loses an email they get today. Change summaries go only to administrators subscribed to the team; import failure notifications go only to administrators who have them on. Every import that ends as error or aborted is also logged, so Nightwatch always sees it regardless of anyone's preferences.

Domain vocabulary follows `CONTEXT.md`: Administrator, Team, Team season, Season, Import, Initial import, Change summary, Calendar feed, Notification subscription, Import failure notification.

## User Stories

1. As an administrator, I want a "Upozornění" page in settings, so that I can manage which emails I receive in one place.
2. As an administrator, I want to see every team of the current season on that page, sorted by name, so that I can find the team I look after quickly.
3. As an administrator, I want a switch next to each team showing whether I am subscribed to its change summaries, so that I can see my current choice at a glance.
4. As an administrator, I want to turn off change summaries for a team I do not look after, so that I stop receiving emails about it.
5. As an administrator, I want to turn change summaries for a team back on, so that I can resume looking after it.
6. As an administrator, I want my choice saved as soon as I flip a switch, without a separate save button, so that I cannot forget to save.
7. As an administrator, I want a toast confirming the switch was saved, so that I know the change took effect.
8. As an administrator, I want to be subscribed to every team by default, so that I do not miss any change summary I receive today.
9. As an administrator, I want to be subscribed automatically to a team another administrator adds later, so that a new team is never silently unwatched.
10. As a newly created administrator, I want to be subscribed to every existing team, so that I start receiving change summaries without configuring anything.
11. As an administrator, I want my notification subscription to survive the team being carried over to a new season, so that I do not have to set it again every season.
12. As an administrator, I want my choice for a team that is not in the current season to be kept, so that it applies again when the team returns.
13. As an administrator, I want teams outside the current season hidden from the page, so that the list only shows teams that can actually produce emails.
14. As an administrator, I want a single "Chyby importu" switch, so that I can choose whether I receive import failure notifications.
15. As an administrator, I want import failure notifications on by default, so that I keep receiving them unless I decide otherwise.
16. As an administrator, I want import failure notifications for every team regardless of my notification subscriptions, so that a failing team that nobody subscribes to is still reported to someone.
17. As an administrator, I want to receive a team's change summary only when I am subscribed to that team, so that my inbox only holds teams I care about.
18. As an administrator subscribed to a team, I want the change summary email to keep its current content and WhatsApp link, so that my workflow of forwarding it to the team does not change.
19. As an administrator, I want no email sent at all when nobody is subscribed to a team, so that the mail provider does not reject a mail without recipients.
20. As an administrator, I want no import failure email sent when every administrator has turned it off, for the same reason.
21. As an administrator, I want the initial import to stay unannounced, so that a new team does not flood my inbox.
22. As an administrator, I want to change only my own preferences, so that I cannot unsubscribe a colleague.
23. As a guest, I want the settings page to require signing in, so that preferences are not exposed.
24. As a maintainer, I want every import that ends as error or aborted logged with its team season, import and reason, so that Nightwatch reports it even when nobody receives an email.
25. As a maintainer, I want a failed connection to ceskyflorbal.cz logged once, not twice, so that Nightwatch is not noisy.
26. As a maintainer, I want existing administrators and teams backfilled when the feature is deployed, so that behaviour is unchanged on the first day.
27. As a maintainer, I want unsubscribing to keep the date it happened, so that I can tell when and whether an administrator turned a team off.
28. As a developer, I want the seeded administrator subscribed to the seeded teams, so that local development sends change summaries like production.
29. As a player subscribed to a team's calendar feed, I want my calendar to keep updating regardless of any administrator's preferences, so that this feature does not affect me.

## Implementation Decisions

- **Schema — notification subscriptions.** New table holding one row per administrator × team pair: user, team (unique together, both cascade on delete), a nullable `unsubscribed_at` timestamp, and timestamps. `unsubscribed_at` null means subscribed; set means unsubscribed since that moment. Rows are never deleted when unsubscribing.
- **Schema — import failure notifications.** New boolean on users, `notify_import_failures`, default `true`.
- **Rows always exist for every pair.** A missing row means "not subscribed", so the app keeps the set complete:
    - One idempotent queued job subscribes every administrator to every team for which no row exists yet, using Eloquent (`firstOrCreate` per pair). It never touches existing rows, so unsubscriptions survive. It is safe to run any number of times.
    - The job is dispatched after commit when a user is created (the user creation action used by the `user:create` command) and when a brand-new team is created together with its first team season. Carrying an existing team over to a new season does not dispatch it.
    - The migration backfills rows for all existing users × teams directly, not via the queue.
    - The demo seeder creates the rows for the seeded administrator and team with factories, like the rest of its data.
    - The window before the job runs is harmless: a new team's first import is the initial import and announces nothing; a new administrator has nothing pending.
- **Models.** A `NotificationSubscription` model with relationships from `User` and `Team`; a way to query a team's subscribed administrators (rows with `unsubscribed_at` null).
- **Sending.** The import action stops emailing every user:
    - Change summary (`FixtureListRevised`): recipients are the administrators subscribed to the imported team season's team.
    - Import failure (`ImportFailed`): recipients are the administrators with `notify_import_failures` on.
    - Still one mail addressed to all recipients at once; nothing is sent when the recipient list is empty.
- **Logging.** Finishing an import without applying always reaches Nightwatch with team season ID, import ID and reason, following `.ai/rules/third-party-api.md`: a download failure a later import may pass on its own (lost connection, 429, 5xx) and an aborted import are a `Log::warning`; a download failure someone has to fix (another 4xx, a page the parser can't read) is `report($e)` so it becomes a Nightwatch issue. An unexpected failure is rethrown and reported by its caller. The existing connection-failure warning is folded into this so a connection failure is logged once.
- **Routes and controllers** (under the existing authenticated, verified settings group):
    - `GET settings/notifications` — renders the Inertia page with the `notify_import_failures` value and the current season's teams (ID, name, subscribed flag), sorted by name.
    - An endpoint to set the import-failure switch for the signed-in administrator.
    - An endpoint to subscribe or unsubscribe the signed-in administrator to a team: unsubscribing sets `unsubscribed_at` to now, subscribing clears it. The team is bound by route; the row affected is always the signed-in user's, so no other administrator's preference can be changed. Unknown team → 404.
    - Each responds with a redirect back and a success toast, matching existing settings controllers.
- **Frontend.** New settings page "Upozornění" added to the settings navigation next to Profile, Security and Appearance, with the usual small `Heading` ("Upozornění" / "Vyberte, jaké e-maily vám mají chodit"). Layout chosen in a UI prototype (variant A): two plain sections built from shadcn `FieldGroup` / `FieldSet` / `FieldLegend` / `Field orientation="horizontal"` / `FieldSeparator`, no cards, no table:
    - Section "Chyby importu": one row — label "Posílat e-mail, když import selže", description "Platí pro všechny týmy, i ty, jejichž změny neodebíráte.", `Switch` on the right.
    - `FieldSeparator`.
    - Section "Změny v rozpisu zápasů" with description "Shrnutí změn, které pak pošlete do WhatsAppu týmu.": one row per team — label is the team name, description is the current team season's competition (omitted when unknown), `Switch` on the right.
    - Each switch submits immediately (Wayfinder route functions, Inertia), no save button; the server's flash toast confirms. Texts in Czech.
- **Glossary.** `CONTEXT.md` already defines Administrator, Calendar feed, Notification subscription and Import failure notification; no ADR.

## Testing Decisions

- Tests exercise external behaviour only — which emails go to whom, what the page shows, what the endpoints change — never the job, model internals or query shape.
- **Sending (seam: `fixtures:import` with faked HTTP and `Mail::fake()`).** Prior art: `ImportFixturesRevisedMailTest`, `ImportFixturesFailuresTest`, `Tests\Support\Ceskyflorbal`.
    - The change summary goes only to subscribed administrators; an unsubscribed administrator does not receive it.
    - No mail when nobody is subscribed.
    - Import failure goes only to administrators with `notify_import_failures` on, regardless of notification subscriptions; no mail when nobody has it on.
    - Recoverable download failures and aborted imports are logged as warnings with team season, import and reason, and a connection failure is logged once (`Log::spy`); download failures that need fixing are reported (`Exceptions::fake()`).
    - Existing tests that create users via the factory must give them notification subscriptions (e.g. a factory state or helper) so they keep expressing "every user receives it".
- **Settings page (seam: HTTP feature tests).** Prior art: `tests/Feature/Settings/ProfileUpdateTest.php`.
    - Guests are redirected to login.
    - The page lists only the current season's teams, sorted by name, with the correct subscribed flags and import-failure flag.
    - Unsubscribing sets the administrator's row as unsubscribed; subscribing again clears it; another administrator's row is untouched.
    - Toggling the import-failure switch updates only the signed-in administrator.
- **Creating administrators and teams (seam: `user:create` and `POST teams`; the queue is `sync` in tests).** Prior art: `CreateUserCommandTest`, `AddTeamToSeasonTest`.
    - A new administrator is subscribed to every existing team.
    - A new team is subscribed by every existing administrator.
    - Carrying a team over to a new season keeps existing unsubscriptions.
- No browser tests, no direct job or model unit tests, no migration or seeder tests (verify the backfill and seeder with `migrate:fresh --seed`).

## Out of Scope

- The calendar feed: it stays anonymous; subscribing to it is unrelated to notification subscriptions.
- Per-team control of import failure notifications.
- Notification channels other than email, and digest/batching of emails.
- A history of every subscribe/unsubscribe; only the latest `unsubscribed_at` is kept.
- A scheduled self-healing run of the subscription job.
- Letting one administrator manage another's preferences.

## Further Notes

- Default-on everywhere preserves today's behaviour on deploy; the only visible change on day one is the new settings page and extra Nightwatch logs.
- Use pnpm for frontend tooling; build UI from shadcn/ui components.
- **Prototype.** The page layout was settled with a throwaway UI prototype: five variants (A list of switch rows, B table with bulk actions, C cards with team chips, D bordered row groups, E caption dividers with bulleted rows) on branch `prototype/team-notification-preferences` (commit `920b181`, local only). Variant A won. The prototype code (`notifications-prototype` page, `PrototypeSwitcher`, the closure route) must not be merged; rewrite the page properly.
