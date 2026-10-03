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
        $startYear = fake()->unique()->numberBetween(2000, 2098);

        return [
            'name' => sprintf('%d/%02d', $startYear, ($startYear + 1) % 100),
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
