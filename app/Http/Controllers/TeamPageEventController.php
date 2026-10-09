<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TeamPageEvents\CreateTeamPageEvent;
use App\Enums\TeamPageAction;
use App\Http\Requests\StoreTeamPageEventRequest;
use App\Models\Team;
use Illuminate\Http\Response;

final readonly class TeamPageEventController
{
    /**
     * Record what a player did on the team's public page, which the page sends as a beacon that nothing waits for.
     */
    public function store(StoreTeamPageEventRequest $request, Team $team, CreateTeamPageEvent $createTeamPageEvent): Response
    {
        $createTeamPageEvent->handle(
            $team,
            $request->enum('action', TeamPageAction::class),
            $request->validated('in_app_browser'),
            (string) $request->userAgent(),
        );

        return response()->noContent();
    }
}
