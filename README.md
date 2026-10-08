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

To release, merge the Release PR. That tags `vX.Y.Z`, publishes a GitHub Release with the same notes, runs the CI checks, pushes the image to GHCR and deploys it to production (see [Deployment](#deployment)).

To force a version, add a `Release-As: x.y.z` footer to a commit on `main`:

```bash
git commit --allow-empty -m "chore: release 1.0.0" -m "Release-As: 1.0.0"
```

## Deployment

`Dockerfile` builds a production image (serversideup/php with FrankenPHP). The release workflow pushes it to `ghcr.io/reinerttomas/matchday`, tagged with the version. `compose.yml` builds nothing: it pulls the version that `IMAGE_TAG` names and runs it as these services:

| Service            | Runs                                                                         |
| ------------------ | ---------------------------------------------------------------------------- |
| `app`              | Octane on port 8080; runs migrations, `optimize` and `storage:link` on start |
| `queue`            | `queue:work`                                                                 |
| `scheduler`        | `schedule:work` (fixture imports)                                            |
| `ssr`              | Inertia SSR server                                                           |
| `nightwatch-agent` | Laravel Nightwatch agent                                                     |

The app runs on a VPS with [Dokploy](https://dokploy.com) as a Docker Compose app:

1. Create a Compose app from this repository with `compose.yml` as the compose file. Turn off **Auto Deploy**, because the release workflow deploys.
2. Let Dokploy pull the image: add `ghcr.io` under **Registry** with your GitHub username and a personal access token (classic) with `read:packages`, or make the package public.
3. Paste [`.env.dokploy`](.env.dokploy) into the **Environment** tab and fill in the `…` values. Dokploy writes it to `.env` next to `compose.yml`.
4. Add a domain for the `app` service on port `8080` with HTTPS.
5. On GitHub, create the `production` environment (**Settings → Environments**) and limit its deployment branches to `main`. Add the secrets `DOKPLOY_URL` (the Dokploy base URL), `DOKPLOY_API_KEY` (an API key from your Dokploy profile) and `DOKPLOY_COMPOSE_ID` (the ID in the compose app's URL).
6. Deploy a released version, see [Deploying and rolling back](#deploying-and-rolling-back).

The app's configuration comes only from Dokploy's `.env`. `compose.yml` sets only what follows from its own services and volumes: the SQLite path and WAL journal mode (all services share one SQLite file on the `sqlite` volume), the addresses of the `ssr` and `nightwatch-agent` services and which service runs migrations on start.

`compose.yml` publishes no host port. Dokploy's Traefik terminates TLS and reaches Octane over the Docker network. Keep it that way: the app trusts the `X-Forwarded-*` headers of every caller, so a published port would let anyone fake the client IP or host.

### Deploying and rolling back

Merging the Release PR deploys the new version. The release workflow's `deploy` job runs [`.infrastructure/dokploy-deploy.sh`](.infrastructure/dokploy-deploy.sh), which sets `IMAGE_TAG` in the Dokploy environment, starts a deploy and waits for its result, so a failed deploy turns the workflow red. Two deploys never run at once.

To roll back, deploy an older version: **Actions → deploy → Run workflow** with its tag, e.g. `0.1.0`. The workflow first checks that the tag exists in GHCR. A rollback keeps the current `compose.yml` and does not undo migrations. If GitHub Actions is down, run the script locally:

```bash
export DOKPLOY_URL=https://… DOKPLOY_COMPOSE_ID=…
read -rs DOKPLOY_API_KEY && export DOKPLOY_API_KEY   # keeps the key out of the shell history
.infrastructure/dokploy-deploy.sh 0.1.0
```
