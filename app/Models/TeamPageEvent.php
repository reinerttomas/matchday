<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamPageAction;
use Database\Factories\TeamPageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property TeamPageAction $action
 * @property string|null $in_app_browser
 * @property string $user_agent
 * @property Carbon $created_at
 * @property-read Team $team
 */
#[Fillable(['team_id', 'action', 'in_app_browser', 'user_agent'])]
final class TeamPageEvent extends Model
{
    /** @use HasFactory<TeamPageEventFactory> */
    use HasFactory;

    use MassPrunable;

    /**
     * Events are never edited after they are recorded.
     */
    public const UPDATED_AT = null;

    /**
     * Get the events older than the 180 days that are kept.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subDays(180));
    }

    /**
     * Get the team whose public page the event happened on.
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
            'action' => TeamPageAction::class,
        ];
    }
}
