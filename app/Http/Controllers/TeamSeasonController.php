<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TeamSeasons\CreateTeamSeason;
use App\Actions\TeamSeasons\UpdateTeamSeason;
use App\Http\Requests\StoreTeamSeasonRequest;
use App\Http\Requests\UpdateTeamSeasonRequest;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Services\AdminSelection;
use App\Services\CarryOverTeamListPresenter;
use App\Services\Ceskyflorbal\FixtureListAddress;
use App\Services\TeamSeasonListPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TeamSeasonController
{
    /**
     * Show the selected season's team seasons and the teams that can be carried over into it, or nothing while there is no season.
     */
    public function index(AdminSelection $adminSelection, TeamSeasonListPresenter $teamSeasonListPresenter, CarryOverTeamListPresenter $carryOverTeamListPresenter): Response
    {
        $season = $adminSelection->season();

        return Inertia::render('teams/index', [
            'teamSeasons' => $season === null ? null : $teamSeasonListPresenter->present($adminSelection->teamSeasons()),
            'carryOverTeams' => $season === null ? [] : $carryOverTeamListPresenter->present(Team::query()
                ->whereDoesntHave('teamSeasons', fn (Builder $teamSeasons): Builder => $teamSeasons->whereBelongsTo($season))
                ->get()),
            // The form previews a new team's calendar address while the slug is typed.
            'calendarUrlTemplate' => route('calendar', ['team' => ':slug']),
        ]);
    }

    /**
     * Add a brand-new team or a team from a previous season to the selected season, start its first import, and return to the page it was added on.
     */
    public function store(StoreTeamSeasonRequest $request, FixtureListAddress $fixtureListAddress, CreateTeamSeason $createTeamSeason): RedirectResponse
    {
        $season = $request->season();
        $externalId = $request->externalId();

        $teamSeason = $createTeamSeason->handle($request->team(), $season, [
            'external_id' => $externalId,
            'source_url' => $fixtureListAddress->for($externalId),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('team_seasons.created', ['team_season' => $teamSeason->displayName(), 'season' => $season->name]),
        ]);

        return back();
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
