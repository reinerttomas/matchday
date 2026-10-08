# 38 — Production Docker image with Laravel Octane

**What to build:** A production `Dockerfile` and `compose.yml` built on the `serversideup/php` FrankenPHP image, serving the app through Laravel Octane in worker mode. One image runs every process: the Octane web server, the queue worker, the scheduler and the Inertia SSR server. The Nightwatch agent runs next to them as its own container. This is for trying Octane out and for a later deployment; hosting is still undecided, so nothing provider-specific goes in.

**Blocked by:** —

**Status:** resolved

- [x] `laravel/octane` is installed as a production dependency and configured for FrankenPHP (`php artisan octane:install --server=frankenphp`). `config/octane.php` and `public/frankenphp-worker.php` are committed; `.env.example` lists `OCTANE_SERVER=frankenphp`. A locally downloaded `frankenphp` binary is not committed.
- [x] Service providers and container singletons are checked for state that leaks between requests under Octane; anything that holds per-request state is fixed.
- [x] `config/inertia.php` reads the SSR URL from `INERTIA_SSR_URL`, defaulting to the current `http://127.0.0.1:13714`.
- [x] `config/database.php` reads the SQLite `busy_timeout` and `journal_mode` from `DB_BUSY_TIMEOUT` and `DB_JOURNAL_MODE`, defaulting to `null` (local behaviour unchanged).
- [x] `bootstrap/app.php` trusts proxies (`trustProxies(at: '*')`), so URLs and redirects behind the reverse proxy use `https`.
- [x] The SSR bundle (`bootstrap/ssr/ssr.mjs`) runs without `node_modules`; if it doesn't, `vite.config.ts` bundles its dependencies (`ssr.noExternal`).
- [x] `Dockerfile` is multi-stage on `serversideup/php:8.4-frankenphp` (Debian): `base` (adds `intl`, `bcmath`), `composer` (no-dev install, cached on `composer.json`/`composer.lock`), `frontend` (Node 22 + pnpm, runs `pnpm run build:ssr` with vendor present for Wayfinder), `deploy` (app owned by `www-data`, Node binary for the SSR server, runs as `www-data`, default command is `octane:start` on port 8080). No secrets end up in the image.
- [x] `.dockerignore` keeps git, env files, editor/AI folders, `vendor`, `node_modules`, build output, local `bootstrap/cache/*.php`, tests, tooling config, docs and `.scratch` out of the build context.
- [x] `compose.yml` defines `app` (Octane, `AUTORUN_ENABLED=true`, `healthcheck-octane`, port `${APP_PORT:-8080}:8080`), `queue` (`queue:work --tries=3`, `healthcheck-queue`), `scheduler` (`schedule:work`, `healthcheck-schedule`), `ssr` (`inertia:start-ssr`) and `nightwatch-agent`. App services share one YAML anchor, read `.env` and get `APP_ENV=production`, `APP_DEBUG=false`, `PHP_OPCACHE_ENABLE=1`, `SSL_MODE=off`, SQLite in a named volume with WAL and a busy timeout, `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION=database`, `INERTIA_SSR_URL=http://ssr:13714` and `NIGHTWATCH_INGEST_URI=nightwatch-agent:2407`. `queue` and `scheduler` wait for a healthy `app`, so migrations have run first.
- [x] Starting the stack on an empty volume creates the SQLite database and runs the migrations.
- [x] `docker compose build` succeeds; with a production `.env`, `docker compose up -d` brings every service to healthy, `/up` returns 200 and `/` returns server-rendered HTML.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-08:
    - **Database:** SQLite in a named volume, shared by `app`, `queue` and `scheduler`. WAL and a busy timeout because Octane workers, the queue and the scheduler all write to it at the same time.
    - **Cache, sessions, queue:** the `database` driver (the `cache`, `cache_locks`, `sessions` and `jobs` tables already exist). Every container uses the same SQLite file, so the cache is shared too, and the scheduler's `withoutOverlapping` lock on the fixture import works across containers. No Redis.
    - **Inertia SSR:** a sidecar container from the same image, built with `build:ssr`.
    - **TLS:** handled by a reverse proxy in front of the stack. The container serves plain HTTP on 8080 (`SSL_MODE=off`).
    - **Image variant:** Debian rather than Alpine. serversideup recommends it for FrankenPHP performance, and it can run the Node binary from `node:22-bookworm-slim`.
- The layout borrows from `reinerttomas/laravel-subtrack-app` (`Dockerfile.php`, `docker-compose.yml`, `.dockerignore`), minus its development and CI stages. This ticket covers production only.
- This is the follow-up promised in ticket 37: the Nightwatch agent now runs as a container. `NIGHTWATCH_ENABLED` and `NIGHTWATCH_TOKEN` come from the production `.env`.
