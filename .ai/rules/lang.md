---
paths:
    - 'lang/**'
    - 'app/**'
    - 'resources/views/**'
---

# Localization

## Texts live in translation files

Read every text the app produces through `__()` or `trans_choice()` from PHP array files under `lang/cs/`; do not hardcode Czech in `app/` or `resources/views/`. Czech (`cs`) is the default locale, so do not pass a locale or set Carbon's locale by hand. Texts the parser compares with ceskyflorbal.cz markup (e.g. "odehráno") describe the source, not our wording, and stay in the parser.

## One file per domain model, in the plural

Name each file after the domain model its texts belong to (see `CONTEXT.md`), in the plural and snake_case: `imports.php`, `fixtures.php`, `team_seasons.php`. Do not name files after a feature or screen, such as `change-summary.php`. Split a file into subsections per use, e.g. `imports.php` → `change_summary`, `errors`, `notifications`, and key texts tied to an enum by its backed value (`statuses.postponed`).

## Placeholders, not concatenation

Write whole phrases with named placeholders (`'nový čas :time (původně :time_before)'`) instead of joining fragments in code, so word order and punctuation stay in the file. Keep keys and placeholders lowercase snake_case; `:Name` and `:NAME` would change the case of the inserted value. Use `trans_choice()` with Czech plural forms for counts: `'{1} :count zápas|[2,4] :count zápasy|[0,*] :count zápasů'`.

## Stored and sent texts

Translate a text when it is written, e.g. the reason stored in `imports.error`, so the stored value is the final Czech text. Markdown in mail texts (`**:team_season**`) is intentional; let Blade's `{{ }}` escape the whole line so inserted values stay escaped.
