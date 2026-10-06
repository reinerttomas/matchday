<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TeamSeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $season_id
 * @property int $external_id
 * @property string $source_url
 * @property string|null $name
 * @property string|null $competition_name
 * @property bool $auto_import_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Season $season
 * @property-read Collection<int, Fixture> $fixtures
 * @property-read Collection<int, Import> $imports
 */
#[Fillable(['team_id', 'season_id', 'external_id', 'source_url', 'name', 'competition_name', 'auto_import_enabled'])]
final class TeamSeason extends Model
{
    /** @use HasFactory<TeamSeasonFactory> */
    use HasFactory;

    /**
     * Get the team taking part in the season.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the season the team takes part in.
     *
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the fixtures in the team season's fixture list.
     *
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    /**
     * Get the imports of the team season's fixture list.
     *
     * @return HasMany<Import, $this>
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Name the team season by its team's name that season, such as "FBC Kutná Hora B".
     */
    public function displayName(): string
    {
        // A team season gets its name from its first ok import, so until then the team's slug stands in for it.
        return $this->name ?? $this->team->slug;
    }

    /**
     * Name the team season by its team's name and the season, such as "FBC Kutná Hora B 2026/27".
     */
    public function displayNameWithSeason(): string
    {
        return "{$this->displayName()} {$this->season->name}";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'auto_import_enabled' => 'boolean',
        ];
    }
}
