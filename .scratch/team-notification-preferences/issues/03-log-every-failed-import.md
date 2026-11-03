# 03 — Log every failed import

**What to build:** Finishing an import without applying it logs every outcome so Nightwatch always sees it: error as `Log::error`, aborted as `Log::warning`, each with team season ID, import ID and reason. The existing connection-failure warning is folded into this so a connection failure is logged once, not twice. Independent of anyone's preferences. See `.scratch/team-notification-preferences/spec.md`, "Logging".

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] An import ending as error is logged with `Log::error` with team season ID, import ID and reason.
- [ ] An import ending as aborted is logged with `Log::warning` with the same context.
- [ ] A failed connection to ceskyflorbal.cz is logged exactly once.
- [ ] Tested through `fixtures:import` with faked HTTP and `Log::spy` / expectations (prior art: `ImportFixturesFailuresTest`).
- [ ] `composer ci:check` passes.
