<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamPageAction;
use App\Models\Team;
use App\Models\TeamPageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamPageEvent>
 */
final class TeamPageEventFactory extends Factory
{
    /**
     * Define the model's default state: a player opening the public team page in a regular browser.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'action' => TeamPageAction::PageView,
            'in_app_browser' => null,
            'user_agent' => fake()->userAgent(),
        ];
    }
}
