# 02 — Email only the administrators who want it

**What to build:** The import action stops emailing every user. The change summary (`FixtureListRevised`) goes to the administrators subscribed to the imported team season's team; the import failure (`ImportFailed`) goes to administrators with `notify_import_failures` on, regardless of subscriptions. Still one mail addressed to all recipients at once; nothing sent when there are no recipients. Existing mail tests keep expressing "every user receives it" by giving their users subscriptions. See `.scratch/team-notification-preferences/spec.md`, "Sending" and "Testing Decisions → Sending".

**Blocked by:** 01 — Keep every administrator subscribed to every team

**Status:** resolved

- [x] The change summary goes only to subscribed administrators; an unsubscribed administrator does not receive it; its content and WhatsApp link are unchanged.
- [x] No change summary mail when nobody is subscribed.
- [x] The import failure mail goes only to administrators with `notify_import_failures` on, regardless of subscriptions; none when nobody has it on.
- [x] The initial import still announces nothing.
- [x] Tested through `fixtures:import` with faked HTTP and `Mail::fake()` (prior art: `ImportFixturesRevisedMailTest`, `ImportFixturesFailuresTest`, `Tests\Support\Ceskyflorbal`).
- [x] `composer ci:check` passes.
