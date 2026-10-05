<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\TeamSeason;
use App\Services\Ceskyflorbal\CeskyflorbalClient;
use App\Services\Ceskyflorbal\FixtureListRowData;
use Illuminate\Support\Facades\DB;

final readonly class ImportTeamSeason
{
    /**
     * Create a new instance.
     */
    public function __construct(private CeskyflorbalClient $ceskyflorbal) {}

    /**
     * Download the team season's fixture list from ceskyflorbal.cz and store its fixtures, recording the run as an import.
     */
    public function handle(TeamSeason $teamSeason, ImportTrigger $trigger): Import
    {
        // Created outside the transaction so a running import is visible while the page downloads, and a failed one stays in the history.
        $import = $teamSeason->imports()->create([
            'trigger' => $trigger,
            'status' => ImportStatus::Running,
            'started_at' => now(),
        ]);

        $page = $this->ceskyflorbal->fixtureList($teamSeason);

        DB::transaction(function () use ($teamSeason, $page, $import): void {
            foreach ($page->rows as $row) {
                $this->apply($teamSeason, $row);
            }

            $teamSeason->update([
                'name' => $page->teamName ?? $teamSeason->name,
                'competition_name' => $page->competitionName ?? $teamSeason->competition_name,
            ]);

            $import->update([
                'status' => ImportStatus::Ok,
                'finished_at' => now(),
                'fixtures_found' => count($page->rows),
            ]);
        });

        return $import;
    }

    /**
     * Create or update the fixture a fixture list row shows.
     */
    private function apply(TeamSeason $teamSeason, FixtureListRowData $row): void
    {
        $fixture = $teamSeason->fixtures()->firstOrNew(['external_id' => $row->externalId]);

        $fixture->fill([
            'round' => $row->round,
            'is_home' => $row->isHome,
            'opponent_name' => $row->opponentName,
            'date' => $row->date,
            'status' => $row->status ?? ($fixture->exists ? $fixture->status : FixtureStatus::Scheduled),
            'is_rescheduled' => $row->isRescheduled,
            'home_score' => $row->homeScore,
            'away_score' => $row->awayScore,
        ]);

        if ($row->showsStartTime) {
            $fixture->time = $row->time;
        }

        $fixture->save();
    }
}
