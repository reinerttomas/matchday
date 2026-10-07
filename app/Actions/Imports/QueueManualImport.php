<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Jobs\ImportFixtureList;
use App\Models\TeamSeason;
use App\Services\ImportProgress;

final readonly class QueueManualImport
{
    /**
     * Create a new instance.
     */
    public function __construct(private ImportProgress $importProgress) {}

    /**
     * Queue a manual import of the team season for the "Synchronizovat" action, so the request doesn't wait for the download.
     *
     * It is queued even while another import of the team season runs, because the import module skips it then.
     */
    public function handle(TeamSeason $teamSeason): void
    {
        // Marked before dispatching, because a synchronous queue runs the job, which clears the mark, during dispatch.
        $this->importProgress->markQueued($teamSeason);

        ImportFixtureList::dispatch($teamSeason);
    }
}
