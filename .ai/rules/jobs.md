---
paths:
    - 'app/Jobs/**'
---

# Jobs

## Use Foundation\Queue\Queueable and take the model, not its id

A queued job uses the single `Illuminate\Foundation\Queue\Queueable` trait, which already bundles `Dispatchable`, `InteractsWithQueue`, `Illuminate\Bus\Queueable` and `SerializesModels` — don't list those four separately.

The constructor takes the model itself as a promoted `public readonly` property, not an id the handler resolves with `findOrFail()`. `SerializesModels` puts only the key in the payload and re-fetches the model on the worker, so there is no staleness to avoid and no extra query to save; a `public` property is also what lets a test assert on a pushed job (`fn (ImportFixtureList $job): bool => $job->teamSeason->is($teamSeason)`). Take an id only where there is no model to hand over.

## Configure a job with queue attributes, not properties or methods

Declare a job's queue settings with the attributes from `Illuminate\Queue\Attributes` — `#[Tries]`, `#[Backoff]`, `#[Timeout]`, `#[MaxExceptions]`, `#[FailOnTimeout]`, `#[UniqueFor]`, `#[DeleteWhenMissingModels]`, `#[Queue]`, `#[Connection]` — instead of a property such as `public $tries` or a method such as `backoff()`, so all of them read at a glance above the class. Import the attribute with `use`, never write it fully qualified. A method stays only for a value computed at runtime (`retryUntil()`, a backoff that depends on the job's data), and then without the attribute: Laravel reads the method over it, so the attribute would be dead.

## Example

```php
#[Timeout(300)]
#[Tries(1)]
final class ImportFixtureList implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TeamSeason $teamSeason) {}

    public function handle(ImportTeamSeason $importTeamSeason): void
    {
        $importTeamSeason->handle($this->teamSeason, ImportTrigger::Manual);
    }
}
```
