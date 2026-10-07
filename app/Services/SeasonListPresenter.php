<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Season;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type SeasonProps array{id: int, name: string, isCurrent: bool, teamSeasonsCount: int}
 */
final readonly class SeasonListPresenter
{
    /**
     * Describe the seasons for the Sezony page, so the administrator sees which one is current and how many team seasons each has.
     *
     * @param  Collection<int, Season>  $seasons
     * @return list<SeasonProps>
     */
    public function present(Collection $seasons): array
    {
        $seasons->loadCount('teamSeasons');

        return array_values($seasons
            ->map(fn (Season $season): array => $this->season($season))
            ->all());
    }

    /**
     * Describe one season as a row of the list.
     *
     * @return SeasonProps
     */
    private function season(Season $season): array
    {
        return [
            'id' => $season->id,
            'name' => $season->name,
            'isCurrent' => $season->is_current,
            'teamSeasonsCount' => (int) $season->getAttribute('team_seasons_count'),
        ];
    }
}
