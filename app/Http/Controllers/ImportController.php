<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Imports\QueueManualImport;
use App\Enums\ImportHistoryFilter;
use App\Services\AdminSelection;
use App\Services\ImportHistoryPresenter;
use App\Services\ImportProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ImportController
{
    /**
     * Show the history of the selected team season's imports, all of them unless only the revising or failed ones are asked for, or nothing when the selected season has no team seasons yet; a page past the end, such as from an edited address, goes to the last page instead.
     */
    public function index(Request $request, AdminSelection $adminSelection, ImportHistoryPresenter $importHistoryPresenter, ImportProgress $importProgress): Response|RedirectResponse
    {
        $teamSeason = $adminSelection->teamSeason();

        $importHistory = $teamSeason === null ? null : $importHistoryPresenter->present(
            $teamSeason,
            ImportHistoryFilter::fromQuery($request->query('filter')),
        );

        $imports = $importHistory['imports'] ?? null;

        if ($imports !== null && $imports->currentPage() > $imports->lastPage()) {
            return redirect($imports->url($imports->lastPage()));
        }

        return Inertia::render('imports/index', [
            'importHistory' => $importHistory,
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
