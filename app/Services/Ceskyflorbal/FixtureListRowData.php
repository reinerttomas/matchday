<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use App\Enums\FixtureStatus;
use Carbon\CarbonImmutable;

final readonly class FixtureListRowData
{
    /**
     * Create one fixture as the team season's fixture list page shows it.
     *
     * @param  bool  $showsStartTime  false on finished rows, which show the score in the start time's place
     * @param  string|null  $time  `HH:MM:SS`, or null for a TBD time or when the row shows no start time
     * @param  string|null  $venueName  null on finished rows, which show "odehráno" in the venue's place
     * @param  FixtureStatus|null  $status  null when the page shows a status the parser doesn't know
     */
    public function __construct(
        public int $externalId,
        public ?int $round,
        public CarbonImmutable $date,
        public bool $showsStartTime,
        public ?string $time,
        public bool $isHome,
        public string $opponentName,
        public ?string $venueName,
        public ?FixtureStatus $status,
        public bool $isRescheduled,
        public ?int $homeScore,
        public ?int $awayScore,
    ) {}
}
