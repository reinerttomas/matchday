<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
final class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->randomNumber(5, strict: true),
            'name' => 'Sportovní hala '.fake()->city(),
            'address' => fake()->streetAddress().', '.fake()->city(),
        ];
    }

    /**
     * Indicate that the venue's address has not been read from a fixture detail page yet.
     */
    public function withoutAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'address' => null,
        ]);
    }
}
