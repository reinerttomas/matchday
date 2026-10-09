<?php

declare(strict_types=1);

namespace App\Actions\CalendarFetches;

use App\Models\CalendarFetch;
use App\Models\Team;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class RecordCalendarFetch
{
    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
    ) {}

    /**
     * Count a fetch of the team's calendar feed under today in the federation's timezone and the calendar app's User-Agent; one atomic upsert, so simultaneous fetches never lose a count.
     */
    public function handle(Team $team, string $userAgent): void
    {
        $userAgent = Str::substr($userAgent, 0, CalendarFetch::USER_AGENT_MAX_LENGTH);

        CalendarFetch::query()->upsert(
            [[
                'team_id' => $team->id,
                // Bound as a date like the model stores it, so the unique key matches rows written either way.
                'date' => today($this->timezone),
                'user_agent' => $userAgent,
                'user_agent_hash' => hash('sha256', $userAgent),
                'count' => 1,
            ]],
            uniqueBy: ['team_id', 'date', 'user_agent_hash'],
            update: ['count' => DB::raw('count + 1')],
        );
    }
}
