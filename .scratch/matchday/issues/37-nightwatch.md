# 37 — Laravel Nightwatch monitoring

**What to build:** The app reports requests, jobs, commands, queries, exceptions and logs to Laravel Nightwatch, so production problems (failed imports, errors on the public team page) show up there. The health check route `/up` is never sent to Nightwatch. Hosting isn't decided yet, so this ticket only covers the app side; running the agent in production comes later.

**Blocked by:** —

**Status:** ready-for-agent

- [ ] `laravel/nightwatch` is installed as a production dependency (`composer require laravel/nightwatch`).
- [ ] `.env.example` lists `NIGHTWATCH_TOKEN=` and `NIGHTWATCH_ENABLED=false`, so a fresh local setup doesn't try to reach an agent. Sample rates stay at the package defaults; no `config/nightwatch.php` is published unless something needs it.
- [ ] Requests to `/up` are never sampled. The route stays registered through `health: '/up'` in `bootstrap/app.php`; a listener for `Illuminate\Foundation\Events\DiagnosingHealth` (dispatched only by the health route) calls `Nightwatch::dontSample()`.
- [ ] Tests keep Nightwatch off (`NIGHTWATCH_ENABLED=false` is already in `phpunit.xml`; CI has no token).
- [ ] A feature test covers that a request to `/up` is not sampled and that another route still is (e.g. via `Nightwatch::sampling()`; if Nightwatch has to be enabled for that test, it must not need a running agent).
- [ ] `composer ci:check` passes.

## Notes

- Decided on 2026-10-08: hosting isn't chosen yet, so no supervisor/daemon config and no Laravel Cloud setup. On Laravel Cloud Nightwatch is switched on in the dashboard; on Forge/VPS `php artisan nightwatch:agent` must run under a process monitor. That is a follow-up ticket once hosting is known.
- `/up` is excluded because uptime checks hit it every few seconds and would drown out real traffic and eat the event quota. The `Sample::never()` route middleware from the docs doesn't fit, because the framework registers the health route itself; the `DiagnosingHealth` listener keeps that registration.
- An exception thrown while diagnosing health is still reported by Laravel (`report()` in the health route), so a broken `/up` isn't silent.
- The spec already expects stale imports to reach Nightwatch through the error log ("Implementation Decisions", imports); nothing changes there.
