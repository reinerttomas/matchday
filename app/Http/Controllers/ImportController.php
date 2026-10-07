<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Imports\QueueManualImport;
use App\Services\AdminSelection;
use App\Services\ImportHistoryPresenter;
use App\Services\ImportProgress;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ImportController
{
    /**
     * Show the history of the selected team season's imports, or nothing when the selected season has no team seasons yet.
     */
    public function index(AdminSelection $adminSelection, ImportHistoryPresenter $importHistoryPresenter, ImportProgress $importProgress): Response
    {
        $teamSeason = $adminSelection->teamSeason();

        return Inertia::render('imports/index', [
            'imports' => $teamSeason === null ? null : $importHistoryPresenter->present($teamSeason),
            'isImportRunning' => $teamSeason !== null && $importProgress->isRunning($teamSeason),
        ]);
    }

    /**
     * Start a manual import of the selected team season in the background, and return to the page the administrator started it on.
     */
    public function store(AdminSelection $adminSelection, QueueManualImport $queueManualImport): RedirectResponse
    {
        $teamSeason = $adminSelection->teamSeason() ?? abort(404);

        $queueManualImport->handle($teamSeason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('imports.manual_import.queued')]);

        return back();
    }
}
