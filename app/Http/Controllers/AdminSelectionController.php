<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdminSelectionRequest;
use App\Models\Season;
use App\Models\TeamSeason;
use App\Services\AdminSelection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Uri;

final readonly class AdminSelectionController
{
    /**
     * Switch the season and team season the administrator works on, and return to the page they switched on.
     */
    public function __invoke(UpdateAdminSelectionRequest $request, AdminSelection $adminSelection): RedirectResponse
    {
        $adminSelection->select(
            Season::query()->findOrFail($request->integer('season_id')),
            $request->filled('team_season_id') ? TeamSeason::query()->findOrFail($request->integer('team_season_id')) : null,
        );

        // A page number of the previous team season's list means nothing for the newly selected one.
        return redirect()->to((string) Uri::of(url()->previous())->withoutQuery(['page']));
    }
}
