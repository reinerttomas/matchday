<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TeamSeason;

final readonly class UnsentChangeSummaries
{
    /**
     * Count the team season's change summaries still waiting to be sent to the team: imports that recorded revisions and aren't marked as sent. The initial import is never announced, so it doesn't count.
     */
    public function count(TeamSeason $teamSeason): int
    {
        return $teamSeason->revisingImports()->whereNull('notified_at')->count();
    }
}
