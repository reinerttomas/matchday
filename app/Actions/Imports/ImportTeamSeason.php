<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Enums\RevisionField;
use App\Models\Fixture;
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
     * Download the team season's fixture list from ceskyflorbal.cz and store its fixtures and their revisions, recording the run as an import.
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
            $isInitialImport = $teamSeason->imports()->where('status', ImportStatus::Ok)->doesntExist();

            foreach ($page->rows as $row) {
                $this->apply($teamSeason, $row, $import, $isInitialImport);
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
     * Create or update the fixture a fixture list row shows and record its revisions, unless this is the initial import.
     */
    private function apply(TeamSeason $teamSeason, FixtureListRowData $row, Import $import, bool $isInitialImport): void
    {
        $fixture = $teamSeason->fixtures()->firstOrNew(['external_id' => $row->externalId]);
        $valuesBefore = $fixture->exists ? $this->revisableValues($fixture) : null;

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

        $revisions = $isInitialImport ? [] : $this->revisions($valuesBefore, $this->revisableValues($fixture));

        if ($fixture->exists && $revisions !== []) {
            $fixture->sequence++;
        }

        $fixture->save();

        $fixture->revisions()->createMany(array_map(
            fn (array $revision): array => [...$revision, 'import_id' => $import->id],
            $revisions,
        ));
    }

    /**
     * Read the fixture's revisable fields as revisions store them, keyed by the revision field.
     *
     * @return array{date: string, time: string|null, status: string, is_rescheduled: string, home_score: string|null, away_score: string|null}
     */
    private function revisableValues(Fixture $fixture): array
    {
        return [
            RevisionField::Date->value => $fixture->date->toDateString(),
            RevisionField::Time->value => $fixture->time === null ? null : mb_substr($fixture->time, 0, 5),
            RevisionField::Status->value => $fixture->status->value,
            RevisionField::IsRescheduled->value => $fixture->is_rescheduled ? '1' : '0',
            RevisionField::HomeScore->value => $fixture->home_score === null ? null : (string) $fixture->home_score,
            RevisionField::AwayScore->value => $fixture->away_score === null ? null : (string) $fixture->away_score,
        ];
    }

    /**
     * List one revision per changed field, or a single revision with no field for a fixture that had no values before.
     *
     * @param  array<string, string|null>|null  $valuesBefore
     * @param  array<string, string|null>  $valuesAfter
     * @return list<array{field: RevisionField|null, old_value: string|null, new_value: string|null}>
     */
    private function revisions(?array $valuesBefore, array $valuesAfter): array
    {
        if ($valuesBefore === null) {
            return [['field' => null, 'old_value' => null, 'new_value' => null]];
        }

        $revisions = [];

        foreach ($valuesAfter as $field => $newValue) {
            if ($newValue !== $valuesBefore[$field]) {
                $revisions[] = ['field' => RevisionField::from($field), 'old_value' => $valuesBefore[$field], 'new_value' => $newValue];
            }
        }

        return $revisions;
    }
}
