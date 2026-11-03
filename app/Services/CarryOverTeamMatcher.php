<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Category;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Finds the team from a previous season that a looked-up federation team continues, so the "Přidat tým" dialog can preselect it.
 *
 * The federation gives a team a new ID every season but usually keeps its name, while our teams can share that name and differ only in category.
 * So the match is the federation name of the team's latest team season together with the team's category, never the name the administrator gave the team.
 */
final readonly class CarryOverTeamMatcher
{
    /**
     * Find the one team, not yet in the season, whose latest team season has the given federation name and which has the given category; null when none or several match.
     */
    public function match(Season $season, string $federationName, Category $category): ?Team
    {
        $matches = Team::query()
            ->where('category', $category)
            ->whereDoesntHave('teamSeasons', fn (Builder $teamSeasons): Builder => $teamSeasons->whereBelongsTo($season))
            ->with('teamSeasons.season')
            ->get()
            ->filter(fn (Team $team): bool => $this->normalised($team->latestTeamSeason()?->name) === $this->normalised($federationName));

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Compare names regardless of case and of surrounding or repeated whitespace.
     */
    private function normalised(?string $name): ?string
    {
        return $name === null ? null : Str::lower(Str::squish($name));
    }
}
