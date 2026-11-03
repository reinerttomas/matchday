<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Users\UpdateUser;
use App\Http\Requests\Settings\UpdateImportFailureNotificationRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ImportFailureNotificationController
{
    /**
     * Turn import failure notifications on or off for the signed-in administrator, for every team at once.
     */
    public function update(UpdateImportFailureNotificationRequest $request, #[CurrentUser] User $user, UpdateUser $updateUser): RedirectResponse
    {
        $notifyImportFailures = $request->boolean('notify_import_failures');

        $updateUser->handle($user, ['notify_import_failures' => $notifyImportFailures]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($notifyImportFailures ? 'users.import_failure_notifications.enabled' : 'users.import_failure_notifications.disabled'),
        ]);

        return back();
    }
}
