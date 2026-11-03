<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NotificationSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An administrator's choice to receive the change summaries of one team; unsubscribed_at is null while subscribed.
 *
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $team_id
 * @property-read Carbon|null $unsubscribed_at
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read User $user
 * @property-read Team $team
 */
#[Fillable(['user_id', 'team_id', 'unsubscribed_at'])]
final class NotificationSubscription extends Model
{
    /** @use HasFactory<NotificationSubscriptionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Stop sending the team's change summaries to the administrator; an administrator already unsubscribed keeps the date they first unsubscribed.
     */
    public function unsubscribe(): void
    {
        if ($this->unsubscribed_at === null) {
            $this->setAttribute('unsubscribed_at', now());
        }
    }

    /**
     * Send the team's change summaries to the administrator again, forgetting when they unsubscribed.
     */
    public function resubscribe(): void
    {
        $this->setAttribute('unsubscribed_at', null);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }
}
