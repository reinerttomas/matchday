<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TeamSeasons\UpdateTeamSeason;
use App\Http\Requests\UpdateTeamSeasonRequest;
use App\Models\TeamSeason;
use App\Services\AdminSelection;
use App\Services\TeamSeasonListPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TeamSeasonController
{
    /**
     * Show the selected season's team seasons, or nothing while there is no season.
     */
    public function index(AdminSelection $adminSelection, TeamSeasonListPresenter $teamSeasonListPresenter): Response
    {
        return Inertia::render('teams/index', [
            'teamSeasons' => $adminSelection->season() === null ? null : $teamSeasonListPresenter->present($adminSelection->teamSeasons()),
        ]);
    }

    /**
     * Switch the team season's auto import on or off, and return to the page it was switched on.
     */
    public function update(UpdateTeamSeasonRequest $request, TeamSeason $teamSeason, UpdateTeamSeason $updateTeamSeason): RedirectResponse
    {
        $autoImportEnabled = $request->boolean('auto_import_enabled');

        $updateTeamSeason->handle($teamSeason, ['auto_import_enabled' => $autoImportEnabled]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($autoImportEnabled ? 'team_seasons.auto_import.enabled' : 'team_seasons.auto_import.disabled', [
                'team_season' => $teamSeason->displayName(),
            ]),
        ]);

        return back();
    }
}
