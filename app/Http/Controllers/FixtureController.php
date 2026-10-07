<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdminSelection;
use App\Services\FixtureListPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FixtureController
{
    /**
     * Show the selected team season's fixture list, its upcoming fixtures unless the whole season is asked for, or nothing when the selected season has no team seasons yet.
     */
    public function index(Request $request, AdminSelection $adminSelection, FixtureListPresenter $fixtureListPresenter): Response
    {
        $teamSeason = $adminSelection->teamSeason();

        return Inertia::render('fixtures/index', [
            'fixtureList' => $teamSeason === null ? null : $fixtureListPresenter->present(
                $teamSeason,
                showsWholeSeason: $request->query('period') === 'season',
            ),
        ]);
    }
}
