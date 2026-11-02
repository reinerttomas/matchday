---
paths:
    - 'app/Models/**'
    - 'app/Actions/**'
    - 'app/Http/Controllers/**'
---

# Models

## Cast enum columns

Cast enum-backed columns to a PHP backed enum in the model's `casts()` method; the column itself is a `string()`, see `.ai/rules/migrations.md`.

## Readonly model PHPDoc

Document every model attribute and relationship with `@property-read`, including attributes the application changes. Type relationship methods with generics containing the related model and `$this`. PHPStan then rejects `$model->attribute = …`, which keeps every change of state explicit (see below).

## Change state through fill(), update() or a domain method

Change a model's attributes through `fill()` or `update()` for a set of attributes, or through a domain method on the model that names what happens (`$fixture->countAsMissing()`, `$fixture->moveToNextSequence()`), never through magic property assignment. Outside the model, do not call `setAttribute()` either: a single attribute that carries a rule or a meaning gets a domain method, and the method uses `setAttribute()` inside. A domain method changes state without saving, so the caller can save it together with its other changes. Use the framework's own method where one exists (`markEmailAsVerified()`, `markEmailAsUnverified()`); those save immediately.

## Name a relationship after the full related model

Name a relationship after the whole related model, never a shortened form: `teamSeasons()` for `TeamSeason`, not `seasons()`. Relationships that pick one record out of many take a qualifier in front of the model (`initialImport()`, `lastImport()`).

## Example

```php
/**
 * @property-read int $id
 * @property-read FixtureStatus $status
 * @property-read int $missing_count
 * @property-read Collection<int, Revision> $revisions
 */
#[Fillable(['status', 'missing_count'])]
final class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    /**
     * Count the fixture as missing from one more fixture list, cancelling it once it is missing from two, so a one-off glitch at the source cancels nothing.
     */
    public function countAsMissing(): void
    {
        $this->setAttribute('missing_count', $this->missing_count + 1);

        if ($this->missing_count >= 2) {
            $this->setAttribute('status', FixtureStatus::Cancelled);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FixtureStatus::class,
        ];
    }
}
```
