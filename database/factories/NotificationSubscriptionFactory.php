<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationSubscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSubscription>
 */
final class NotificationSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state: an administrator subscribed to a team.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'team_id' => Team::factory(),
            'unsubscribed_at' => null,
        ];
    }

    /**
     * Indicate that the administrator has unsubscribed from the team.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'unsubscribed_at' => now()->subDay(),
        ]);
    }
}
