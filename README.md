# Matchday

Matchday publishes a floorball team's fixture list from [ceskyflorbal.cz](https://www.ceskyflorbal.cz) as a calendar people can subscribe to, and tells the team what changed.

During the season the federation keeps updating the fixture list: it fills in start times, switches venues and moves fixtures to other dates. Every four hours Matchday downloads the fixture lists of our teams and records each change. It then:

- **serves a permanent calendar per team** (`/calendar/{slug}.ics`). Players subscribe once in Google, Apple or Outlook calendar and get every later change automatically, across seasons too.
- **shows a public team page** (`/t/{slug}`) with upcoming fixtures and one-tap subscribe buttons.
- **prepares a change summary** for every import that found changes. The administrator sends it to the team's WhatsApp group and marks it as sent.

The administrator manages seasons, teams and venues, and can browse any team's fixtures, revisions and imports, past seasons included.

## Documentation

| Where                                     | What                                                                        |
| ----------------------------------------- | --------------------------------------------------------------------------- |
| [`CONTEXT.md`](CONTEXT.md)                | Domain language: fixture, team season, import, revision, … Read this first. |
| [`docs/adr/`](docs/adr)                   | Architecture decisions                                                      |
| [`.scratch/matchday/`](.scratch/matchday) | Product spec and implementation tickets                                     |
| [`AGENTS.md`](AGENTS.md)                  | Conventions for coding agents (and humans)                                  |

## Stack

- PHP 8.5, Laravel 13, Octane on FrankenPHP
- Inertia v3 + React 19 with SSR, Tailwind CSS 4, shadcn/ui, Wayfinder
- Fortify (password, passkeys, 2FA) and Google login via Socialite
- SQLite, database queue, cache and sessions
- Pest 5 (including browser tests), PHPStan (Larastan), Pint, Vite+ (`vp`)
- Laravel Nightwatch for monitoring in production

## Local development

You need PHP 8.5, Composer, Node 22 and pnpm. The app runs on [Laravel Herd](https://herd.laravel.com) at `https://matchday.test`.

```bash
composer setup              # install dependencies, create .env, generate a key, migrate, build assets
php artisan db:seed         # optional: test@example.com plus demo seasons, teams and fixtures
composer dev                # Vite, queue worker and logs (artisan dev)
```

Create an administrator account:

```bash
php artisan user:create
```

To sign in with Google, set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`.

### Importing fixtures

The scheduler runs `fixtures:import` every four hours for every team season of the current season that has automatic import on. To run it by hand:

```bash
php artisan fixtures:import                # all auto-import team seasons of the current season
php artisan fixtures:import {teamSeason}   # one team season by ID
```

The command imports synchronously. Imports started from the admin UI ("Synchronizovat", adding a team to a season) go through the queue, so `composer dev` (or `php artisan queue:work`) must be running.

## Checks

```bash
composer ci:check   # vp check, tsc, Pint, PHPStan and Pest: the same as CI
composer lint       # fix PHP formatting
pnpm run check:fix  # fix frontend lint and formatting
php artisan test --compact --filter=…
```

Browser tests need Playwright: `pnpm exec playwright install chromium`.

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org) (`feat: …`, `fix: …`). Run `composer ci:check` before every commit.

## Deployment

`Dockerfile` builds a production image (serversideup/php with FrankenPHP). `compose.yml` runs it as these services:

| Service            | Runs                                                                         |
| ------------------ | ---------------------------------------------------------------------------- |
| `app`              | Octane on port 8080; runs migrations, `optimize` and `storage:link` on start |
| `queue`            | `queue:work`                                                                 |
| `scheduler`        | `schedule:work` (fixture imports)                                            |
| `ssr`              | Inertia SSR server                                                           |
| `nightwatch-agent` | Laravel Nightwatch agent                                                     |

```bash
cp .env.example .env   # set APP_KEY, APP_URL, GOOGLE_*, NIGHTWATCH_TOKEN
docker compose up -d --build
```

All services share one SQLite file in WAL mode on the `sqlite` volume. Octane binds to `127.0.0.1:8080` by default (`APP_BIND`, `APP_PORT`). Put a reverse proxy that terminates TLS in front of it, because the app trusts the proxy's `X-Forwarded-*` headers.
