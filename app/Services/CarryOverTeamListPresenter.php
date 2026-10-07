<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type CarryOverTeamProps array{id: int, name: string, slug: string}
 */
final readonly class CarryOverTeamListPresenter
{
    /**
     * Describe the teams the administrator can carry over into a season, alphabetically, each named after its latest team season so it is recognisable.
     *
     * @param  Collection<int, Team>  $teams
     * @return list<CarryOverTeamProps>
     */
    public function present(Collection $teams): array
    {
        $teams->loadMissing('teamSeasons.season');

        return array_values($teams
            ->map(fn (Team $team): array => $this->team($team))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all());
    }

    /**
     * Describe one team as an option to carry over.
     *
     * @return CarryOverTeamProps
     */
    private function team(Team $team): array
    {
        /** @var TeamSeason|null $latestTeamSeason */
        $latestTeamSeason = $team->teamSeasons->sortByDesc(fn (TeamSeason $teamSeason): string => $teamSeason->season->name)->first();

        // The display name falls back to the team's slug; the team is already at hand, so it isn't loaded again.
        $latestTeamSeason?->setRelation('team', $team);

        return [
            'id' => $team->id,
            'name' => $latestTeamSeason?->displayNameWithSeason() ?? $team->slug,
            'slug' => $team->slug,
        ];
    }
}
