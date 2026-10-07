<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

final readonly class FixtureListPageData
{
    /**
     * Create the parsed fixture list page of a team season.
     *
     * @param  string  $seasonName  the season the page's team header shows, named like our seasons ("2026/2027")
     * @param  list<FixtureListRowData>  $rows
     */
    public function __construct(
        public string $seasonName,
        public ?string $teamName,
        public ?string $competitionName,
        public array $rows,
    ) {}
}
