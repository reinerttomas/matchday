<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TeamSeason> $teamSeasons
 * @property-read TeamSeason|null $currentTeamSeason
 */
#[Fillable(['name', 'slug'])]
final class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Get the team's participations in seasons.
     *
     * @return HasMany<TeamSeason, $this>
     */
    public function teamSeasons(): HasMany
    {
        return $this->hasMany(TeamSeason::class);
    }

    /**
     * Get the team's participation in the current season, which the calendar feed and the public team page show.
     *
     * @return HasOne<TeamSeason, $this>
     */
    public function currentTeamSeason(): HasOne
    {
        return $this->hasOne(TeamSeason::class)
            ->whereRelation('season', 'is_current', true)
            ->chaperone();
    }

    /**
     * Name the team's calendar after the team.
     */
    public function calendarName(): string
    {
        return $this->name;
    }
}
