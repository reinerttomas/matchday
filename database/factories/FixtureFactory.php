<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\TeamSeason;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
final class FixtureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_season_id' => TeamSeason::factory(),
            'external_id' => fake()->unique()->randomNumber(6, strict: true),
            'round' => fake()->numberBetween(1, 22),
            'is_home' => fake()->boolean(),
            'opponent_name' => 'FBC '.fake()->city(),
            'venue_id' => Venue::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+4 months')->format('Y-m-d'),
            'time' => fake()->randomElement(['10:00:00', '13:30:00', '17:00:00', '19:15:00']),
            'status' => FixtureStatus::Scheduled,
            'is_rescheduled' => false,
            'home_score' => null,
            'away_score' => null,
            'sequence' => 0,
            'missing_count' => 0,
        ];
    }

    /**
     * Indicate that the federation has not set the fixture's start time yet.
     */
    public function tbdTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'time' => null,
        ]);
    }

    /**
     * Indicate that the fixture has been played and has a score.
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-4 months', '-1 day')->format('Y-m-d'),
            'status' => FixtureStatus::Finished,
            'home_score' => fake()->numberBetween(0, 12),
            'away_score' => fake()->numberBetween(0, 12),
        ]);
    }

    /**
     * Indicate that the fixture was moved off its date with no replacement date known.
     */
    public function postponed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixtureStatus::Postponed,
        ]);
    }

    /**
     * Indicate that the fixture will not be played.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixtureStatus::Cancelled,
        ]);
    }

    /**
     * Indicate that the fixture was postponed and already has a replacement date.
     */
    public function rescheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixtureStatus::Scheduled,
            'is_rescheduled' => true,
        ]);
    }

    /**
     * Indicate that our team plays the fixture at home.
     */
    public function home(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_home' => true,
        ]);
    }

    /**
     * Indicate that our team plays the fixture away.
     */
    public function away(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_home' => false,
        ]);
    }
}
