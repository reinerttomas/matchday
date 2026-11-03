<?php

declare(strict_types=1);

namespace App\Actions\NotificationSubscriptions;

use App\Models\NotificationSubscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class CreateNotificationSubscriptions
{
    /**
     * Subscribe every administrator to every team they have no notification subscription for yet.
     *
     * Existing notification subscriptions are left alone, so an unsubscribed administrator stays unsubscribed and the action is safe to run any number of times.
     */
    public function handle(): void
    {
        User::query()->each(function (User $user): void {
            Team::query()
                ->whereDoesntHave('notificationSubscriptions', fn (Builder $query): Builder => $query->whereBelongsTo($user))
                ->each(fn (Team $team): NotificationSubscription => NotificationSubscription::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                ]));
        });
    }
}
