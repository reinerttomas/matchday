<?php

declare(strict_types=1);

namespace App\Actions\NotificationSubscriptions;

use App\Models\Team;
use App\Models\User;

final readonly class UpdateNotificationSubscription
{
    /**
     * Subscribe the administrator to the team's change summaries or unsubscribe them, keeping the row so the date of unsubscribing is known.
     *
     * @param  array{subscribed: bool}  $attributes
     */
    public function handle(User $user, Team $team, array $attributes): void
    {
        $notificationSubscription = $user->notificationSubscriptions()->firstOrNew(['team_id' => $team->id]);

        if ($attributes['subscribed']) {
            $notificationSubscription->resubscribe();
        } else {
            $notificationSubscription->unsubscribe();
        }

        $notificationSubscription->save();
    }
}
