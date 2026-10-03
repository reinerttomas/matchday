<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamSeason>
 */
final class TeamSeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $externalId = fake()->unique()->randomNumber(5, strict: true);

        return [
            'team_id' => Team::factory(),
            'season_id' => Season::factory(),
            'external_id' => $externalId,
            'source_url' => "https://www.ceskyflorbal.cz/team/detail/matches/{$externalId}",
            'name' => 'FBC '.fake()->city(),
            'competition_name' => 'Liga '.fake()->word(),
            'auto_import_enabled' => true,
        ];
    }

    /**
     * Indicate that the team season has not been imported yet.
     */
    public function notImported(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'competition_name' => null,
        ]);
    }

    /**
     * Indicate that the team season is skipped by automatic imports.
     */
    public function autoImportDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_import_enabled' => false,
        ]);
    }
}
