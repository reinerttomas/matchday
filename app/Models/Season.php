<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_current
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TeamSeason> $teamSeasons
 */
#[Fillable(['name', 'is_current'])]
final class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /**
     * Get the team seasons played in this season.
     *
     * @return HasMany<TeamSeason, $this>
     */
    public function teamSeasons(): HasMany
    {
        return $this->hasMany(TeamSeason::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }
}
