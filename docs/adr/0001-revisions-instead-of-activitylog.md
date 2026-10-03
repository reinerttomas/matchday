# Fixture history in a dedicated revisions table, not spatie/laravel-activitylog

The original spec chose spatie/laravel-activitylog for fixture change history. We use our own `revisions` table instead: one row per changed field (`fixture_id`, `import_id`, `field`, `old_value`, `new_value`), shaped like venturecraft/revisionable. Every consumer of the history (the "changed" badge with old → new tooltip, the changes page grouped by import, the WhatsApp change summary, change counts per import) reads individual field changes. Real foreign keys to the fixture and the import that detected the change serve those queries better than activitylog's per-event JSON blobs and polymorphic `causer`, which would also need overriding because it defaults to the authenticated user. It also avoids a new dependency.

## Consequences

- A row with `field = null` records a fixture that appeared after the team season's initial import.
- Values are stored as raw strings (ISO date, `HH:MM` or null for TBD time, venue **name** rather than ID, status enum value) and formatted only for display.
- Admin edits (venue addresses) are not audited.
