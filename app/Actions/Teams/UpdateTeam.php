<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Models\Team;

final readonly class UpdateTeam
{
    /**
     * Update the team's name, which every season of the team shows; the slug, and with it the calendar address players subscribed to, stays the same.
     *
     * @param  array{name: string}  $attributes
     */
    public function handle(Team $team, array $attributes): void
    {
        $team->update($attributes);
    }
}
