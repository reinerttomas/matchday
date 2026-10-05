<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\TeamSeason;
use App\Models\Venue;
use App\Services\Ceskyflorbal\FixtureListPageData;
use App\Services\Ceskyflorbal\FixtureListRowData;
use App\Services\Ceskyflorbal\MatchDetailPageData;
use Illuminate\Support\Facades\DB;

final readonly class ApplyFixtureList
{
    /**
     * Store the fixtures, venues and revisions of a downloaded fixture list in one transaction, and finish the import as ok.
     *
     * A stored fixture the list no longer shows is counted as missing, and cancelled once it is missing from two consecutive fixture lists, so a one-off glitch at the source cancels nothing.
     *
     * @param  array<int, MatchDetailPageData>  $matchDetails  keyed by the fixture's external ID
     */
    public function handle(TeamSeason $teamSeason, Import $import, FixtureListPageData $page, array $matchDetails): void
    {
        DB::transaction(function () use ($teamSeason, $import, $page, $matchDetails): void {
            $isInitialImport = $teamSeason->imports()->where('status', ImportStatus::Ok)->doesntExist();
            $storedFixtures = $teamSeason->fixtures()->with('venue')->get()->keyBy('external_id');

            foreach ($page->rows as $row) {
                $this->apply(
                    $storedFixtures->get($row->externalId) ?? $teamSeason->fixtures()->make(['external_id' => $row->externalId]),
                    $row,
                    $matchDetails[$row->externalId] ?? null,
                    $import,
                    $isInitialImport,
                );
            }

            $missingFixtures = $storedFixtures
                ->whereNotIn('external_id', array_map(fn (FixtureListRowData $row): int => $row->externalId, $page->rows))
                ->reject(fn (Fixture $fixture): bool => $fixture->status === FixtureStatus::Cancelled);

            foreach ($missingFixtures as $missingFixture) {
                $this->countMissing($missingFixture, $import, $isInitialImport);
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
    }

    /**
     * Update the fixture a fixture list row shows, and its venue when the match detail page was downloaded, recording its revisions unless this is the initial import.
     */
    private function apply(Fixture $fixture, FixtureListRowData $row, ?MatchDetailPageData $matchDetail, Import $import, bool $isInitialImport): void
    {
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
            'missing_count' => 0,
        ]);

        if ($row->showsStartTime) {
            $fixture->time = $row->time;
        }

        if ($matchDetail !== null) {
            $fixture->venue()->associate($this->venue($matchDetail));
        }

        $this->saveWithRevisions($fixture, $valuesBefore, $import, $isInitialImport);
    }

    /**
     * Count the fixture as missing from one more fixture list, cancelling it once it reaches two.
     */
    private function countMissing(Fixture $fixture, Import $import, bool $isInitialImport): void
    {
        $valuesBefore = $this->revisableValues($fixture);

        $fixture->missing_count++;

        if ($fixture->missing_count >= 2) {
            $fixture->status = FixtureStatus::Cancelled;
        }

        $this->saveWithRevisions($fixture, $valuesBefore, $import, $isInitialImport);
    }

    /**
     * Save the fixture and record a revision for each revisable field it changed, unless this is the initial import, moving an existing fixture to its next sequence when it has any.
     *
     * @param  array<string, string|null>|null  $valuesBefore  null for a fixture that is not stored yet
     */
    private function saveWithRevisions(Fixture $fixture, ?array $valuesBefore, Import $import, bool $isInitialImport): void
    {
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
     * Find the venue a match detail page shows by its federation ID, creating it or updating its name and filling in a missing address.
     */
    private function venue(MatchDetailPageData $matchDetail): Venue
    {
        $venue = Venue::query()->firstOrNew(['external_id' => $matchDetail->venueExternalId]);

        $venue->name = $matchDetail->venueName;
        // Administrators correct addresses by hand, so only a missing one is taken from the page.
        $venue->address ??= $matchDetail->venueAddress;
        $venue->save();

        return $venue;
    }

    /**
     * Read the fixture's revisable fields as revisions store them, keyed by the revision field.
     *
     * @return array{date: string, time: string|null, venue: string|null, status: string, is_rescheduled: string, home_score: string|null, away_score: string|null}
     */
    private function revisableValues(Fixture $fixture): array
    {
        return [
            RevisionField::Date->value => $fixture->date->toDateString(),
            RevisionField::Time->value => $fixture->time === null ? null : mb_substr($fixture->time, 0, 5),
            RevisionField::Venue->value => $fixture->venue?->name,
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
