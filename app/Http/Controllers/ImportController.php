<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdminSelection;
use App\Services\ImportHistoryPresenter;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ImportController
{
    /**
     * Show the history of the selected team season's imports, or nothing when the selected season has no team seasons yet.
     */
    public function index(AdminSelection $adminSelection, ImportHistoryPresenter $importHistoryPresenter): Response
    {
        $teamSeason = $adminSelection->teamSeason();

        return Inertia::render('imports/index', [
            'imports' => $teamSeason === null ? null : $importHistoryPresenter->present($teamSeason),
        ]);
    }
}
