<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Models\Import;

final readonly class MarkChangeSummaryAsSent
{
    /**
     * Record that the team was sent the import's change summary, so it no longer waits to be sent; an import marked before keeps when the team was first told.
     */
    public function handle(Import $import): void
    {
        if ($import->notified_at !== null) {
            return;
        }

        $import->update(['notified_at' => now()]);
    }
}
