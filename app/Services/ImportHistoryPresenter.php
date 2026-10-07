<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImportHistoryFilter;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @phpstan-type ImportProps array{id: int, startedOn: string, startedAt: string, trigger: string, status: string, statusLabel: string, reason: string|null, duration: string|null, fixturesFoundLabel: string|null, revisionsCount: int, revisionsLabel: string}
 * @phpstan-type ImportHistoryProps array{filter: string, allCount: int, revisedCount: int, failedCount: int, imports: LengthAwarePaginator<int, ImportProps>}
 */
final readonly class ImportHistoryPresenter
{
    /**
     * Imports shown on one page of the history.
     */
    private const int PER_PAGE = 20;

    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
    ) {}

    /**
     * List the team season's imports for the Importy page, newest first and narrowed by the filter, so the administrator can see when the fixture list was downloaded and investigate problems; each filter's tab counts its imports over the whole history.
     *
     * @return ImportHistoryProps
     */
    public function present(TeamSeason $teamSeason, ImportHistoryFilter $filter): array
    {
        return [
            'filter' => $filter->value,
            'allCount' => $this->filtered($teamSeason->imports(), ImportHistoryFilter::All)->count(),
            'revisedCount' => $this->filtered($teamSeason->imports(), ImportHistoryFilter::Revised)->count(),
            'failedCount' => $this->filtered($teamSeason->imports(), ImportHistoryFilter::Failed)->count(),
            'imports' => $this->filtered($teamSeason->imports(), $filter)
                ->withCount('revisions')
                ->latest('started_at')
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->appends($filter === ImportHistoryFilter::All ? [] : ['filter' => $filter->value])
                ->through(fn (Import $import): array => $this->import($import)),
        ];
    }

    /**
     * Narrow the imports to the ones the filter lists.
     *
     * @param  HasMany<Import, TeamSeason>  $imports
     * @return HasMany<Import, TeamSeason>
     */
    private function filtered(HasMany $imports, ImportHistoryFilter $filter): HasMany
    {
        return match ($filter) {
            ImportHistoryFilter::All => $imports,
            ImportHistoryFilter::Revised => $imports->has('revisions'),
            ImportHistoryFilter::Failed => $imports->whereIn('status', [ImportStatus::Error, ImportStatus::Aborted]),
        };
    }

    /**
     * Describe one import as a row of the history.
     *
     * @return ImportProps
     */
    private function import(Import $import): array
    {
        $startedAt = $import->started_at->toImmutable()->setTimezone($this->timezone);
        $revisionsCount = (int) $import->getAttribute('revisions_count');

        return [
            'id' => $import->id,
            'startedOn' => $startedAt->format('j. n. Y'),
            'startedAt' => $startedAt->format('H:i'),
            'trigger' => $import->trigger->value,
            'status' => $import->status->value,
            'statusLabel' => $import->status->label(),
            'reason' => in_array($import->status, [ImportStatus::Error, ImportStatus::Aborted], true) ? $import->error : null,
            'duration' => $this->duration($import),
            'fixturesFoundLabel' => $import->fixtures_found === null ? null : trans_choice('imports.history.fixtures_found', $import->fixtures_found),
            'revisionsCount' => $revisionsCount,
            'revisionsLabel' => trans_choice('imports.history.revisions', $revisionsCount),
        ];
    }

    /**
     * Tell how long a finished import took, such as "1 min 5 s".
     */
    private function duration(Import $import): ?string
    {
        if ($import->finished_at === null) {
            return null;
        }

        $seconds = (int) $import->started_at->diffInSeconds($import->finished_at, absolute: true);

        if ($seconds < 60) {
            return __('imports.history.duration.seconds', ['seconds' => $seconds]);
        }

        return __('imports.history.duration.minutes', ['minutes' => intdiv($seconds, 60), 'seconds' => $seconds % 60]);
    }
}
