<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seasons\MarkSeasonAsCurrent;
use App\Models\Season;
use App\Services\AdminSelection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class CurrentSeasonController
{
    /**
     * Mark the season as current, and return to the page it was marked on.
     */
    public function store(Season $season, AdminSelection $adminSelection, MarkSeasonAsCurrent $markSeasonAsCurrent): RedirectResponse
    {
        // The selected season defaults to the current one; the administrator keeps working on the season they had until they switch.
        $adminSelection->keep();

        $markSeasonAsCurrent->handle($season);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('seasons.marked_as_current', ['season' => $season->name])]);

        return back();
    }
}
