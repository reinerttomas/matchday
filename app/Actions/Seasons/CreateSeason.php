<?php

declare(strict_types=1);

namespace App\Actions\Seasons;

use App\Models\Season;

final readonly class CreateSeason
{
    /**
     * Create a season that is not current yet, so the administrator can prepare it before calendars and automatic imports switch to it.
     *
     * @param  array{name: string}  $attributes
     */
    public function handle(array $attributes): Season
    {
        return Season::query()->create([...$attributes, 'is_current' => false]);
    }
}
