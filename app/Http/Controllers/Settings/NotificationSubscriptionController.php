<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\NotificationSubscriptions\UpdateNotificationSubscription;
use App\Http\Requests\Settings\UpdateNotificationSubscriptionRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class NotificationSubscriptionController
{
    /**
     * Subscribe the signed-in administrator to the team's change summaries or unsubscribe them; no other administrator's choice is reachable.
     */
    public function update(
        UpdateNotificationSubscriptionRequest $request,
        #[CurrentUser] User $user,
        Team $team,
        UpdateNotificationSubscription $updateNotificationSubscription,
    ): RedirectResponse {
        $subscribed = $request->boolean('subscribed');

        $updateNotificationSubscription->handle($user, $team, ['subscribed' => $subscribed]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($subscribed ? 'notification_subscriptions.subscribed' : 'notification_subscriptions.unsubscribed', [
                'team' => $team->name,
            ]),
        ]);

        return back();
    }
}
