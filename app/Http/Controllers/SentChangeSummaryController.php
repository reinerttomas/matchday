<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Imports\MarkChangeSummaryAsSent;
use App\Models\Import;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class SentChangeSummaryController
{
    /**
     * Mark the change summary of an import to announce as sent, and return to the page it was sent from.
     */
    public function store(Import $import, MarkChangeSummaryAsSent $markChangeSummaryAsSent): RedirectResponse
    {
        abort_unless($import->teamSeason->revisingImports()->whereKey($import->id)->exists(), 404);

        $markChangeSummaryAsSent->handle($import);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('imports.changes.marked_as_sent')]);

        return back();
    }
}
