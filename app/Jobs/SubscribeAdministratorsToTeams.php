<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\NotificationSubscriptions\CreateNotificationSubscriptions;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Completes the notification subscriptions once a new administrator or a new team exists.
 *
 * Until it runs nothing is lost: a new team's first import is the initial import, which announces nothing, and a new administrator has nothing pending.
 */
final class SubscribeAdministratorsToTeams implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(CreateNotificationSubscriptions $createNotificationSubscriptions): void
    {
        $createNotificationSubscriptions->handle();
    }
}
