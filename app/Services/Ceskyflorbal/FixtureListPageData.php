<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

final readonly class FixtureListPageData
{
    /**
     * Create the parsed fixture list page of a team season.
     *
     * @param  list<FixtureListRowData>  $rows
     */
    public function __construct(
        public ?string $teamName,
        public ?string $competitionName,
        public array $rows,
    ) {}
}
