<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Imports\ImportTeamSeason;
use App\Enums\ImportTrigger;
use App\Models\TeamSeason;
use App\Services\ImportProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

/**
 * Imports a team season for the manual "Synchronizovat" action, so the HTTP request doesn't wait for the download.
 *
 * An initial import pauses between about 25 page requests, which outlasts the worker's default 60 second timeout, so the job allows 300 seconds. A failed import is not retried; the next scheduled one tries again.
 */
#[Timeout(300)]
#[Tries(1)]
final class ImportFixtureList implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public TeamSeason $teamSeason) {}

    /**
     * Execute the job.
     */
    public function handle(ImportTeamSeason $importTeamSeason, ImportProgress $importProgress): void
    {
        try {
            $importTeamSeason->handle($this->teamSeason, ImportTrigger::Manual);
        } finally {
            $importProgress->forgetQueued($this->teamSeason);
        }
    }
}
