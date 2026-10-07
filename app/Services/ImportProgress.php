<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Contracts\Cache\Repository;

final readonly class ImportProgress
{
    /**
     * Create a new instance.
     */
    public function __construct(private Repository $cache) {}

    /**
     * Determine whether an import of the team season is under way, which disables "Synchronizovat" and keeps the admin pages polling.
     *
     * A manual import counts from the moment it is queued, because the page reloads before a worker picks up the job and would otherwise stop polling straight away. A running import the import module takes for dead doesn't count, so the administrator can start a new one.
     */
    public function isRunning(TeamSeason $teamSeason): bool
    {
        return $this->cache->has($this->queuedKey($teamSeason))
            || $teamSeason->imports()->alive()->exists();
    }

    /**
     * Remember that a manual import of the team season waits in the queue.
     *
     * The mark expires like a dead running import, so a job that never runs, such as one queued while no worker is up, doesn't disable the button for good.
     */
    public function markQueued(TeamSeason $teamSeason): void
    {
        $this->cache->put($this->queuedKey($teamSeason), true, now()->addMinutes(Import::DEAD_RUNNING_IMPORT_MINUTES));
    }

    /**
     * Forget that a manual import of the team season waits in the queue, once its job has run.
     */
    public function forgetQueued(TeamSeason $teamSeason): void
    {
        $this->cache->forget($this->queuedKey($teamSeason));
    }

    /**
     * Get the cache key that marks a queued manual import of the team season.
     */
    private function queuedKey(TeamSeason $teamSeason): string
    {
        return "imports:team-season:{$teamSeason->id}:queued";
    }
}
