<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revision>
 */
final class RevisionFactory extends Factory
{
    /**
     * Define the model's default state: a fixture whose TBD time was set.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            // Keeps the fixture in the same team season as the import that revised it.
            'fixture_id' => fn (array $attributes) => Fixture::factory()->state([
                'team_season_id' => Import::query()->whereKey($attributes['import_id'])->value('team_season_id'),
            ]),
            'field' => RevisionField::Time,
            'old_value' => null,
            'new_value' => fake()->randomElement(['10:00', '13:30', '17:00', '19:15']),
        ];
    }

    /**
     * Indicate that the revision records one fixture field going from the old value to the new value.
     */
    public function fieldChange(RevisionField $field, ?string $oldValue, ?string $newValue): static
    {
        return $this->state(fn (array $attributes) => [
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    /**
     * Indicate that the fixture appeared after the team season's initial import.
     */
    public function fixtureAdded(): static
    {
        return $this->state(fn (array $attributes) => [
            'field' => null,
            'old_value' => null,
            'new_value' => null,
        ]);
    }
}
