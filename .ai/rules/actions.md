---
paths:
    - 'app/Actions/**'
    - 'app/Http/Controllers/**'
    - 'app/Jobs/**'
    - 'app/Console/**'
---

# Actions

## Single-purpose Action classes

Put domain writes in `final readonly` classes under `app/Actions/{Domain}`, named verb + resource without an `Action` suffix, with a single public method `handle()`. No base class. An operation shared by several callers is still an action — a controller, a job, a command and another action call it the same way — so it does not need a service to hold it. Work that only a queued job runs stays in the job; see `jobs.md`.

## Inject actions and pass runtime data to handle

Resolve actions through Laravel's container: controllers, console commands, jobs, observers and other actions receive them through dependency injection. Inject stable collaborators through the constructor; pass request-time models and validated values to `handle()`. Do not use `auth()`, `session()` or `request()` inside an action.

## The acting user is an argument

An action takes the user who performs it as its first argument, `handle(User $user, TeamSeason $teamSeason, array $attributes)`, as Fortify's own actions do. The controller hands the user over from `#[CurrentUser]`. A user the action works on, rather than the one performing it, is ordinary input.

## Validated input as an array shape

Pass validated input as an array and describe it with an array shape in the PHPDoc (`@param array{auto_import_enabled: bool} $attributes`), as `UpdateTeamSeason` does. Do not introduce Data or InputData classes for it.

## Use CRUD names for resource mutations

Prefer `Create{Resource}`, `Update{Resource}` and `Delete{Resource}` for resource mutations. Keep a domain-specific verb only when the operation is not naturally CRUD (`MarkSeasonAsCurrent`, `QueueManualImport`), and name it after what it does in the domain, not after a metaphor.

## Example

```php
public function update(UpdateTeamSeasonRequest $request, TeamSeason $teamSeason, UpdateTeamSeason $updateTeamSeason): RedirectResponse
{
    $updateTeamSeason->handle($teamSeason, $request->validated());

    return back();
}

final readonly class UpdateTeamSeason
{
    /**
     * @param  array{auto_import_enabled: bool}  $attributes
     */
    public function handle(TeamSeason $teamSeason, array $attributes): void
    {
        $teamSeason->update($attributes);
    }
}
```
