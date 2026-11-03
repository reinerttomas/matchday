<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type CarryOverTeamProps array{id: int, name: string, category: array{value: string, label: string}, slug: string}
 */
final readonly class CarryOverTeamListPresenter
{
    /**
     * Describe the teams the administrator can carry over into a season, alphabetically by name.
     *
     * @param  Collection<int, Team>  $teams
     * @return list<CarryOverTeamProps>
     */
    public function present(Collection $teams): array
    {
        return array_values($teams
            ->map(fn (Team $team): array => $this->team($team))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all());
    }

    /**
     * Describe one team as an option to carry over, with its category for the badge that tells same-named teams apart.
     *
     * @return CarryOverTeamProps
     */
    private function team(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'category' => ['value' => $team->category->value, 'label' => $team->category->label()],
            'slug' => $team->slug,
        ];
    }
}
