<?php

declare(strict_types=1);

namespace App\Actions\TeamPageEvents;

use App\Enums\TeamPageAction;
use App\Models\Team;
use App\Models\TeamPageEvent;
use Illuminate\Support\Str;

final readonly class CreateTeamPageEvent
{
    /**
     * Record what a player did on the team's public page and from which browser, without anything that identifies the player.
     */
    public function handle(Team $team, TeamPageAction $action, ?string $inAppBrowser, string $userAgent): TeamPageEvent
    {
        return TeamPageEvent::query()->create([
            'team_id' => $team->id,
            'action' => $action,
            'in_app_browser' => $inAppBrowser,
            'user_agent' => Str::substr($userAgent, 0, TeamPageEvent::USER_AGENT_MAX_LENGTH),
        ]);
    }
}
