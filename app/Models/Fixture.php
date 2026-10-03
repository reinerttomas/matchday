<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FixtureStatus;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_season_id
 * @property int $external_id
 * @property int|null $round
 * @property bool $is_home
 * @property string $opponent_name
 * @property int|null $venue_id
 * @property Carbon $date
 * @property string|null $time
 * @property FixtureStatus $status
 * @property bool $is_rescheduled
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int $sequence
 * @property int $missing_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TeamSeason $teamSeason
 * @property-read Venue|null $venue
 */
#[Fillable([
    'team_season_id',
    'external_id',
    'round',
    'is_home',
    'opponent_name',
    'venue_id',
    'date',
    'time',
    'status',
    'is_rescheduled',
    'home_score',
    'away_score',
    'sequence',
    'missing_count',
])]
final class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * Get the team season whose fixture list contains the fixture.
     *
     * @return BelongsTo<TeamSeason, $this>
     */
    public function teamSeason(): BelongsTo
    {
        return $this->belongsTo(TeamSeason::class);
    }

    /**
     * Get the venue the fixture is played at.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
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
            'is_home' => 'boolean',
            'date' => 'date',
            'status' => FixtureStatus::class,
            'is_rescheduled' => 'boolean',
        ];
    }
}
