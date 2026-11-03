<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationSubscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Completes the notification subscriptions once a new administrator or a new team exists.
 *
 * Until it runs nothing is lost: a new team's first import is the initial import, which announces nothing, and a new administrator has nothing pending.
 *
 * It creates only the rows that are missing, so an unsubscribed administrator stays unsubscribed and the job is safe to run any number of times.
 */
final class SubscribeAdministratorsToTeams implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Subscribe every administrator to every team without a notification subscription yet; unsubscribed administrators stay unsubscribed.
     */
    public function handle(): void
    {
        User::query()->each(function (User $user): void {
            Team::query()->each(fn (Team $team): NotificationSubscription => $user->notificationSubscriptions()->firstOrCreate([
                'team_id' => $team->id,
            ]));
        });
    }
}
