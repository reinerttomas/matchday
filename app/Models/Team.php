<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TeamSeason> $teamSeasons
 */
#[Fillable(['slug'])]
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
}
