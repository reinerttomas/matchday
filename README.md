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

## Releasing

[release-please](https://github.com/googleapis/release-please) keeps one open Release PR that bumps the version and adds the new `CHANGELOG.md` entries. Each push to `main` updates it: `feat` bumps the minor version, `fix` and `perf` the patch, and before 1.0 a breaking change bumps the minor too. Commits of the other types stay out of the changelog.

To release, merge the Release PR. That tags `vX.Y.Z` and publishes a GitHub Release with the same notes.

To force a version, add a `Release-As: x.y.z` footer to a commit on `main`:

```bash
git commit --allow-empty -m "chore: release 1.0.0" -m "Release-As: 1.0.0"
```

## Deployment

`Dockerfile` builds a production image (serversideup/php with FrankenPHP). `compose.yml` runs it as these services:

| Service            | Runs                                                                         |
| ------------------ | ---------------------------------------------------------------------------- |
| `app`              | Octane on port 8080; runs migrations, `optimize` and `storage:link` on start |
| `queue`            | `queue:work`                                                                 |
| `scheduler`        | `schedule:work` (fixture imports)                                            |
| `ssr`              | Inertia SSR server                                                           |
| `nightwatch-agent` | Laravel Nightwatch agent                                                     |

The app runs on a VPS with [Dokploy](https://dokploy.com) as a Docker Compose app:

1. Create a Compose app from this repository with `compose.yml` as the compose file.
2. Paste [`.env.dokploy`](.env.dokploy) into the **Environment** tab and fill in the `…` values. Dokploy writes it to `.env` next to `compose.yml`.
3. Add a domain for the `app` service on port `8080` with HTTPS.
4. Deploy.

The app's configuration comes only from that `.env`. `compose.yml` sets only what follows from its own services and volumes: the SQLite path and WAL journal mode (all services share one SQLite file on the `sqlite` volume), the addresses of the `ssr` and `nightwatch-agent` services and which service runs migrations on start.

`compose.yml` publishes no host port. Dokploy's Traefik terminates TLS and reaches Octane over the Docker network. Keep it that way: the app trusts the `X-Forwarded-*` headers of every caller, so a published port would let anyone fake the client IP or host.
