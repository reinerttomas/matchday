<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\TeamSeason;
use App\Services\Ceskyflorbal\CeskyflorbalClient;
use App\Services\Ceskyflorbal\FixtureListPageData;
use App\Services\Ceskyflorbal\MatchDetailPageData;
use Illuminate\Support\Facades\Log;

final readonly class ImportTeamSeason
{
    /**
     * Create a new instance.
     */
    public function __construct(
        private CeskyflorbalClient $ceskyflorbal,
        private ApplyFixtureList $applyFixtureList,
    ) {}

    /**
     * Download the team season's fixture list and the match detail pages it needs from ceskyflorbal.cz and apply them, recording the run as an import.
     */
    public function handle(TeamSeason $teamSeason, ImportTrigger $trigger): Import
    {
        // Created outside the transaction so a running import is visible while the pages download, and a failed one stays in the history.
        $import = $teamSeason->imports()->create([
            'trigger' => $trigger,
            'status' => ImportStatus::Running,
            'started_at' => now(),
        ]);

        $page = $this->ceskyflorbal->fixtureList($teamSeason);

        // Downloaded before the fixture list is applied, so its transaction never holds the database locked while the paced requests wait.
        $matchDetails = $this->matchDetails($teamSeason, $page);

        $this->applyFixtureList->handle($teamSeason, $import, $page, $matchDetails);

        return $import;
    }

    /**
     * Download the match detail page of each fixture that is new or whose venue in the fixture list differs from the stored venue's name.
     *
     * Finished rows show no venue, so they never need one.
     *
     * @return array<int, MatchDetailPageData> keyed by the fixture's external ID
     */
    private function matchDetails(TeamSeason $teamSeason, FixtureListPageData $page): array
    {
        $storedFixtures = $teamSeason->fixtures()->with('venue')->get()->keyBy('external_id');
        $matchDetails = [];

        foreach ($page->rows as $row) {
            if ($row->venueName !== null && $row->venueName !== $storedFixtures->get($row->externalId)?->venue?->name) {
                $matchDetail = $this->ceskyflorbal->matchDetail($row->externalId);

                if ($matchDetail->venueName !== $row->venueName) {
                    Log::warning('The match detail page names the venue differently than the fixture list, so it is downloaded again on every import.', [
                        'external_id' => $row->externalId,
                        'fixture_list_venue' => $row->venueName,
                        'match_detail_venue' => $matchDetail->venueName,
                    ]);
                }

                $matchDetails[$row->externalId] = $matchDetail;
            }
        }

        return $matchDetails;
    }
}
