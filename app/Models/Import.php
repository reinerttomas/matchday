<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_season_id
 * @property ImportTrigger $trigger
 * @property ImportStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property int|null $fixtures_found
 * @property string|null $error
 * @property Carbon|null $notified_at
 * @property-read TeamSeason $teamSeason
 * @property-read Collection<int, Revision> $revisions
 */
#[Fillable([
    'team_season_id',
    'trigger',
    'status',
    'started_at',
    'finished_at',
    'fixtures_found',
    'error',
    'notified_at',
])]
#[WithoutTimestamps]
final class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    /**
     * Get the team season whose fixture list was imported.
     *
     * @return BelongsTo<TeamSeason, $this>
     */
    public function teamSeason(): BelongsTo
    {
        return $this->belongsTo(TeamSeason::class);
    }

    /**
     * Get the revisions the import recorded.
     *
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => ImportTrigger::class,
            'status' => ImportStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }
}
