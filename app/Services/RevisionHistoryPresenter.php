<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\TeamSeason;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * @phpstan-import-type MatchupPart from FixtureFormatter
 * @phpstan-import-type RevisionPart from FixtureFormatter
 *
 * @phpstan-type RevisedFixtureProps array{id: int, day: string, matchup: list<MatchupPart>, revisions: list<list<RevisionPart>>}
 * @phpstan-type AddedFixtureProps array{id: int, day: string, matchup: list<MatchupPart>}
 * @phpstan-type RevisingImportProps array{id: int, startedAt: string, notified: string|null, fixtures: list<RevisedFixtureProps>}
 * @phpstan-type InitialImportProps array{id: int, startedAt: string, summary: string, fixtures: list<AddedFixtureProps>}
 * @phpstan-type RevisionHistoryProps array{imports: list<RevisingImportProps>, initialImport: InitialImportProps|null}
 */
final readonly class RevisionHistoryPresenter
{
    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
        private FixtureFormatter $fixtureFormatter,
    ) {}

    /**
     * Describe what changed in the team season's fixture list and when, one entry per import that recorded revisions, newest first, and whether the team has been told about it; the initial import comes separately, as it is never announced.
     *
     * Every revision of the team season is shown, so all fixtures are loaded with all their revisions at once and grouped by import in memory.
     *
     * @return RevisionHistoryProps
     */
    public function present(TeamSeason $teamSeason): array
    {
        $fixtures = $teamSeason->fixtures()
            ->chaperone()
            ->with(['venue', 'revisions' => fn (Relation $revisions): Relation => $revisions->orderBy('id')])
            ->get()
            ->keyBy('id');
        $revisionsByImport = $fixtures->flatMap(fn (Fixture $fixture): Collection => $fixture->revisions)->groupBy('import_id');
        $initialImport = $teamSeason->initialImport;

        $revisingImports = $teamSeason->revisingImports()
            ->latest('started_at')
            ->latest('id')
            ->get();

        return [
            'imports' => array_values($revisingImports
                ->map(fn (Import $import): array => $this->revisingImport($import, $revisionsByImport->get($import->id, new Collection), $fixtures))
                ->all()),
            'initialImport' => $initialImport === null ? null : $this->initialImport($initialImport, $fixtures),
        ];
    }

    /**
     * Describe an import that recorded revisions by the fixtures it revised, and whether the team has been told about it.
     *
     * @param  Collection<int, Revision>  $revisions
     * @param  Collection<int, Fixture>  $fixtures  keyed by ID
     * @return RevisingImportProps
     */
    private function revisingImport(Import $import, Collection $revisions, Collection $fixtures): array
    {
        $revisionsByFixture = $revisions->groupBy('fixture_id');

        return [
            'id' => $import->id,
            'startedAt' => $this->startedAt($import),
            'notified' => $this->notified($import),
            'fixtures' => array_values(array_map(
                fn (array $fixture): array => [
                    ...$fixture,
                    'revisions' => array_values(array_map(
                        $this->fixtureFormatter->revisionParts(...),
                        $revisionsByFixture->get($fixture['id'], new Collection)->all(),
                    )),
                ],
                $this->inOrderAfter($import, $fixtures->only($revisionsByFixture->keys()->all()))->all(),
            )),
        ];
    }

    /**
     * Tell when the team was sent the import's change summary, such as "Odesláno týmu 6. 10. 2026 v 08:15", or return null while it hasn't been.
     */
    private function notified(Import $import): ?string
    {
        if ($import->notified_at === null) {
            return null;
        }

        return __('imports.changes.notified', $this->dateAndTime($import->notified_at));
    }

    /**
     * Describe the initial import as the number of fixtures it added and the fixtures themselves; those added by later imports carry their own revision.
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return InitialImportProps
     */
    private function initialImport(Import $import, Collection $fixtures): array
    {
        $initialFixtures = $fixtures->reject(fn (Fixture $fixture): bool => $fixture->revisions->contains(fn (Revision $revision): bool => $revision->field === null));
        $count = $import->fixtures_found ?? $initialFixtures->count();

        return [
            'id' => $import->id,
            'startedAt' => $this->startedAt($import),
            'summary' => trans_choice('imports.changes.initial_import', $count, ['count' => $count]),
            'fixtures' => array_values($this->inOrderAfter($import, $initialFixtures)->all()),
        ];
    }

    /**
     * Name the fixtures by their day and sides, as they were right after the import, in the order the change summary lists them: by date and time, a TBD time first.
     *
     * @param  Collection<array-key, Fixture>  $fixtures
     * @return Collection<int, AddedFixtureProps>
     */
    private function inOrderAfter(Import $import, Collection $fixtures): Collection
    {
        return $fixtures
            ->map(function (Fixture $fixture) use ($import): array {
                $values = $fixture->revisableValuesAfter($import);

                return [
                    'sortKey' => [(string) $values[RevisionField::Date->value], $values[RevisionField::Time->value] ?? '', $fixture->id],
                    'fixture' => [
                        'id' => $fixture->id,
                        'day' => $this->fixtureFormatter->day(CarbonImmutable::parse((string) $values[RevisionField::Date->value])),
                        'matchup' => $this->fixtureFormatter->matchup($fixture),
                    ],
                ];
            })
            ->sort(fn (array $fixture, array $otherFixture): int => $fixture['sortKey'] <=> $otherFixture['sortKey'])
            ->pluck('fixture')
            ->values();
    }

    /**
     * Name the import by when it started, the way the Importy page does, such as "6. 10. 2026 08:00".
     */
    private function startedAt(Import $import): string
    {
        return __('imports.history.started_at', $this->dateAndTime($import->started_at));
    }

    /**
     * Split a moment into its date and time in the federation's timezone, for a translation's placeholders.
     *
     * @return array{date: string, time: string}
     */
    private function dateAndTime(CarbonInterface $moment): array
    {
        $moment = $moment->toImmutable()->setTimezone($this->timezone);

        return ['date' => $this->fixtureFormatter->date($moment), 'time' => $moment->format('H:i')];
    }
}
