<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationSettingsController
{
    /**
     * Show which emails the administrator receives: import failure notifications and the change summaries of each team of the current season.
     */
    public function edit(#[CurrentUser] User $user): Response
    {
        $teams = Team::query()
            ->whereHas('currentTeamSeason')
            ->with('currentTeamSeason')
            ->withExists(['subscribedUsers as subscribed' => fn (Builder $query): Builder => $query->whereKey($user->id)])
            ->orderBy('name')
            ->get();

        return Inertia::render('settings/notifications', [
            'notifyImportFailures' => $user->notify_import_failures,
            'teams' => $teams->map(fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'category' => ['value' => $team->category->value, 'label' => $team->category->label()],
                'competition' => $team->currentTeamSeason?->competition_name,
                'subscribed' => (bool) $team->getAttribute('subscribed'),
            ])->all(),
        ]);
    }
}
