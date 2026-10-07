<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seasons\CreateSeason;
use App\Http\Requests\StoreSeasonRequest;
use App\Models\Season;
use App\Services\AdminSelection;
use App\Services\SeasonListPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SeasonController
{
    /**
     * Show every season, newest first, with the current one marked and how many team seasons each has.
     */
    public function index(SeasonListPresenter $seasonListPresenter): Response
    {
        return Inertia::render('seasons/index', [
            'seasons' => $seasonListPresenter->present(Season::query()->orderByDesc('name')->get()),
        ]);
    }

    /**
     * Create a season to prepare before it starts, and return to the page it was created on.
     */
    public function store(StoreSeasonRequest $request, AdminSelection $adminSelection, CreateSeason $createSeason): RedirectResponse
    {
        // Without a current season the newest one is shown by default, which the new season would otherwise become.
        $adminSelection->keep();

        $season = $createSeason->handle(['name' => $request->string('name')->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('seasons.created', ['season' => $season->name])]);

        return back();
    }
}
