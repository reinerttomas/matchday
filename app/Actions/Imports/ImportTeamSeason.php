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
use App\Models\Venue;
use App\Services\Ceskyflorbal\CeskyflorbalClient;
use App\Services\Ceskyflorbal\FixtureListPageData;
use App\Services\Ceskyflorbal\FixtureListRowData;
use App\Services\Ceskyflorbal\MatchDetailPageData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ImportTeamSeason
{
    /**
     * Create a new instance.
     */
    public function __construct(private CeskyflorbalClient $ceskyflorbal) {}

    /**
     * Download the team season's fixture list and the match detail pages it needs from ceskyflorbal.cz, and store its fixtures, venues and revisions, recording the run as an import.
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
        $storedFixtures = $teamSeason->fixtures()->with('venue')->get()->keyBy('external_id');

        // Downloaded before the transaction, so it never holds the database locked while the paced requests wait.
        $matchDetails = $this->matchDetails($page, $storedFixtures);

        DB::transaction(function () use ($teamSeason, $page, $storedFixtures, $matchDetails, $import): void {
            $isInitialImport = $teamSeason->imports()->where('status', ImportStatus::Ok)->doesntExist();

            foreach ($page->rows as $row) {
                $this->apply(
                    $storedFixtures->get($row->externalId) ?? $teamSeason->fixtures()->make(['external_id' => $row->externalId]),
                    $row,
                    $matchDetails[$row->externalId] ?? null,
                    $import,
                    $isInitialImport,
                );
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
     * Download the match detail page of each fixture that is new or whose venue in the fixture list differs from the stored venue's name.
     *
     * Finished rows show no venue, so they never need one.
     *
     * @param  Collection<int, Fixture>  $storedFixtures  keyed by external ID
     * @return array<int, MatchDetailPageData> keyed by the fixture's external ID
     */
    private function matchDetails(FixtureListPageData $page, Collection $storedFixtures): array
    {
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
        ]);

        if ($row->showsStartTime) {
            $fixture->time = $row->time;
        }

        if ($matchDetail !== null) {
            $fixture->venue()->associate($this->venue($matchDetail));
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
