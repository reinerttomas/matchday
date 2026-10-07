<?php

declare(strict_types=1);

namespace App\Actions\TeamSeasons;

use App\Models\TeamSeason;

final readonly class UpdateTeamSeason
{
    /**
     * Update the team season's settings; switching off auto import leaves its fixture list and calendar as they are, only scheduled imports skip it.
     *
     * @param  array{auto_import_enabled: bool}  $attributes
     */
    public function handle(TeamSeason $teamSeason, array $attributes): void
    {
        $teamSeason->update($attributes);
    }
}
