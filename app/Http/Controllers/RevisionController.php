<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdminSelection;
use App\Services\RevisionHistoryPresenter;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RevisionController
{
    /**
     * Show the imports that changed the selected team season's fixture list, or nothing when the selected season has no team seasons yet.
     */
    public function index(AdminSelection $adminSelection, RevisionHistoryPresenter $revisionHistoryPresenter): Response
    {
        $teamSeason = $adminSelection->teamSeason();

        return Inertia::render('changes/index', [
            'revisionHistory' => $teamSeason === null ? null : $revisionHistoryPresenter->present($teamSeason),
        ]);
    }
}
