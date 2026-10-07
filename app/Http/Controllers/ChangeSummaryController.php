<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Import;
use App\Services\ChangeSummaryWriter;
use Illuminate\Http\JsonResponse;

final readonly class ChangeSummaryController
{
    /**
     * Serve the change summary of an import to announce, for the WhatsApp dialog; the summary is null when none of the import's revisions is worth telling the team.
     */
    public function show(Import $import, ChangeSummaryWriter $changeSummaryWriter): JsonResponse
    {
        abort_unless($import->teamSeason->revisingImports()->whereKey($import->id)->exists(), 404);

        return response()->json(['summary' => $changeSummaryWriter->write($import)]);
    }
}
