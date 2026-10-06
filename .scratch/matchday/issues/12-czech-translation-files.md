# 12 — Czech translation files

**What to build:** Every Czech text the app produces lives in translation files under `lang/cs/` instead of being hardcoded, and Czech is the app's default locale. Files are named after the domain model the texts belong to (see `CONTEXT.md`), with a subsection per use, so the wording of one area is easy to find and change without touching code.

**Blocked by:** 11 — Change summary email, 13 — Admin notifications

**Status:** ready-for-agent

- [ ] `config/app.php` and `.env.example` default `APP_LOCALE` to `cs`. The fallback locale stays `en`, so the starter kit's English `__()` keys (auth, settings) keep working until they are translated.
- [ ] `lang/cs/` exists with one file per domain model, each split into subsections, e.g. `lang/cs/import.php` with:
    - `change_summary`: the header, the calendar line and every bullet phrase, with status phrases keyed by the `FixtureStatus` value (plus a separate key for a cancelled fixture that reappears, and one for a finished fixture with a score),
    - `errors`: the reasons stored in `imports.error` (HTTP failures, unreadable page, season mismatch, the "Parser vrátil …" count with its plural forms, unexpected error, "Import nebyl dokončen."),
    - `notifications`: the subjects, headings, body texts and button labels of the failed-import and change-summary notifications' emails.
- [ ] Code reads texts through `__()` / `trans_choice()` with named placeholders instead of string concatenation; no Czech text is left hardcoded in `app/` or `resources/views/`.
- [ ] Texts that match the source's markup (e.g. "odehráno" in `FixtureListParser`) stay in the parser: they describe ceskyflorbal.cz, not our wording.
- [ ] With `cs` as the default locale, the explicit `cs` locale for Czech weekday names in the change summary is no longer needed and is removed.
- [ ] The produced texts are unchanged: the existing tests asserting the change summary, the import reasons and both emails pass without changes to their expected strings.
- [ ] `composer ci:check` passes.

## Notes

- `lang/` is a new base folder, approved by the user. Create only `lang/cs/`; don't run `php artisan lang:publish`.
- Laravel replaces placeholders in one pass, so a `:` in a venue or opponent name can't break a phrase. Keep placeholder names lowercase: `:Name` and `:NAME` change the case of the inserted value.
- No `label()` methods on the enums yet. They come with the first admin page that shows a status badge, and their texts then go to the matching domain model's file (e.g. `lang/cs/fixture.php` → `statuses`).
