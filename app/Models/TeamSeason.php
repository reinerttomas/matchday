<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TeamSeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
