<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

final readonly class MatchDetailPageData
{
    /**
     * Create the venue of a fixture as its match detail page shows it.
     *
     * @param  int  $venueExternalId  the federation's arena ID
     * @param  string|null  $venueAddress  the address lines joined by commas, or null when the page shows none
     */
    public function __construct(
        public int $venueExternalId,
        public string $venueName,
        public ?string $venueAddress,
    ) {}
}
