<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Import>
 */
final class ImportFactory extends Factory
{
    /**
     * Define the model's default state: a finished, successful scheduled import nobody has been told about yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_season_id' => TeamSeason::factory(),
            'trigger' => ImportTrigger::Schedule,
            'status' => ImportStatus::Ok,
            'started_at' => fake()->dateTimeBetween('-1 month', '-1 hour'),
            'finished_at' => fn (array $attributes) => Carbon::parse($attributes['started_at'])->addSeconds(fake()->numberBetween(5, 60)),
            'fixtures_found' => fake()->numberBetween(18, 26),
            'error' => null,
            'notified_at' => null,
        ];
    }

    /**
     * Indicate that the import is still in progress.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Running,
            'finished_at' => null,
            'fixtures_found' => null,
        ]);
    }

    /**
     * Indicate that fetching the fixture list failed.
     */
    public function error(string $reason = 'HTTP 403 – požadavek zablokován'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Error,
            'fixtures_found' => null,
            'error' => $reason,
        ]);
    }

    /**
     * Indicate that an abort rule stopped the import before any data was written.
     */
    public function aborted(string $reason = 'Parser vrátil 0 zápasů (minule 24)'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Aborted,
            'fixtures_found' => 0,
            'error' => $reason,
        ]);
    }

    /**
     * Indicate that the administrator started the import by hand.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger' => ImportTrigger::Manual,
        ]);
    }

    /**
     * Indicate that the team has been told about the import's revisions.
     */
    public function notified(): static
    {
        return $this->state([
            'notified_at' => fn (array $attributes) => Carbon::parse($attributes['finished_at'])->addMinute(),
        ]);
    }
}
