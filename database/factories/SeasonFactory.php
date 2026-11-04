<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
final class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Starts past the years tests name their seasons by hand (2024–2028), so a random name never collides with one.
        $startYear = fake()->unique()->numberBetween(2030, 2098);

        return [
            'name' => sprintf('%d/%d', $startYear, $startYear + 1),
            'is_current' => false,
        ];
    }

    /**
     * Indicate that the season is the current one.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
