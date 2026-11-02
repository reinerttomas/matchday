---
paths:
    - 'app/**'
    - 'database/**'
---

# App

## One concept, one name

Before naming a new class, method, relation, column, factory state, prop or translation key, find how the app already names the concept — `CONTEXT.md` first (its `_Avoid_` lines list the synonyms not to use), then a grep over `app/` — and reuse that name. A synonym makes the reader ask whether the two things differ, and a grep for one name misses the other. Existing synonyms stay until their code is reworked for its own reason; don't copy them into new code.

## No custom Events

Decouple side effects with Eloquent Observers (`#[ObservedBy]`) and `dispatch(new Job())`, not custom Event classes with their own listeners. Listening to framework events (`NotificationSent`, queue, auth or Nightwatch events, such as `DontSampleHealthCheck`) is fine: the listener hooks into a process Laravel already runs.

## Place a class by its role

A class outside Laravel's own directories goes where its role puts it: domain writes in `app/Actions/{Domain}` (see `.ai/rules/actions.md`), integrations and domain computations that write nothing in `app/Services` (see `.ai/rules/services.md`). `app/Support` takes only code that knows nothing about the domain; a class that names a domain concept does not belong there, however small it is.

## Read env() only in config files

Call `env()` only inside `config/*.php`; everywhere else read the value through `config()` or the `#[Config]` container attribute.

## Eloquent over raw SQL

Build queries with Eloquent (`whereHas()`, subqueries, `pluck()`). Reach for `DB::raw()`, `orderByRaw()` or `DB::table()` only when Eloquent cannot express the query, parameterize it and say why in a comment.

## Type query callbacks and qualify columns

Type every closure parameter in a query (`fn (Builder $query)`) and wrap column names in `$query->qualifyColumn()`, so the query stays unambiguous once a join is added.

## Compare a date column against today()

Check the column type before a range query. A `date` column, such as `fixtures.date`, compares against `today()`; a `dateTime` or `timestamp` column against `now()` — `date >= now()` drops today's fixtures. Normalize the PHP value, never wrap the column in `DATE()`.

## Collections over arrays

Keep a sequence in a Collection while working with it and convert with `->all()` only at the boundary that needs an array (`insert()`, an Inertia prop, a third-party API). Check emptiness with `isEmpty()` / `isNotEmpty()`, not `=== []` or `count()`.

## Laravel helpers over hand-written checks

Use the helper where it says the same thing shorter: `filled()` / `blank()` instead of `$x !== null && $x !== ''`, `data_get()` instead of `isset()` or `??` chains over a nested array, `transform()` instead of `$x === null ? null : f($x)`, `rescue()` for a call that may fail and only needs reporting. `filled()` and `blank()` treat a whitespace-only string, an empty array and an empty collection as blank, so check the old condition did not mean to keep them. Over a `mixed` value keep `is_string()` in front, it is what narrows the type.

## Str and Number over native functions

Use `Str` instead of `str_*`, `mb_*` and `preg_*`, and `Number` instead of `number_format()`. Use a native function only where there is no equivalent (`sprintf()`, `hash()`, `base64_encode()`). Check the output when replacing an existing call, it is not always byte-identical.

## Dispatch jobs with dispatch(new Job())

Dispatch with `dispatch(new ImportFixtureList($teamSeason))`, not `ImportFixtureList::dispatch($teamSeason)`, so the constructor arguments are type-checked.
