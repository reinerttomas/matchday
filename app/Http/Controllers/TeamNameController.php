<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Teams\UpdateTeam;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class TeamNameController
{
    /**
     * Rename the team in every season it plays, and return to the page it was renamed on.
     */
    public function update(UpdateTeamRequest $request, Team $team, UpdateTeam $updateTeam): RedirectResponse
    {
        $updateTeam->handle($team, ['name' => $request->string('name')->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('teams.renamed', ['team' => $team->name])]);

        return back();
    }
}
