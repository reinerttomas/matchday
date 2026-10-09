<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarFetch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarFetch>
 */
final class CalendarFetchFactory extends Factory
{
    /**
     * Define the model's default state: a calendar app that fetched the feed a few times today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'date' => today(config('services.ceskyflorbal.timezone')),
            'user_agent' => fake()->randomElement(['Google-Calendar-Importer', 'iOS/18.0 (22A3354) dataaccessd/1.0', 'Microsoft Office/16.0']),
            'user_agent_hash' => fn (array $attributes): string => hash('sha256', $attributes['user_agent']),
            'count' => fake()->numberBetween(1, 8),
        ];
    }
}
