<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CalendarFetchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property Carbon $date
 * @property string $user_agent
 * @property string $user_agent_hash
 * @property int $count
 * @property-read Team $team
 */
#[Fillable(['team_id', 'date', 'user_agent', 'user_agent_hash', 'count'])]
#[WithoutTimestamps]
final class CalendarFetch extends Model
{
    /** @use HasFactory<CalendarFetchFactory> */
    use HasFactory;

    use MassPrunable;

    /**
     * Get the counts of days before the 180 days that are kept.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('date', '<', today('Europe/Prague')->subDays(180));
    }

    /**
     * Get the team whose calendar feed was fetched.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'count' => 'integer',
        ];
    }
}
