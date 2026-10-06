<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Container\Attributes\Config;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @phpstan-type ImportProps array{id: int, startedAt: string, trigger: string, triggerLabel: string, status: string, statusLabel: string, reason: string|null, duration: string|null, fixturesFound: int|null, revisionsCount: int}
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
     * List the team season's imports for the Importy page, newest first, so the administrator can see when the fixture list was downloaded and investigate problems.
     *
     * @return LengthAwarePaginator<int, ImportProps>
     */
    public function present(TeamSeason $teamSeason): LengthAwarePaginator
    {
        return $teamSeason->imports()
            ->withCount('revisions')
            ->latest('started_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (Import $import): array => $this->import($import));
    }

    /**
     * Describe one import as a row of the history.
     *
     * @return ImportProps
     */
    private function import(Import $import): array
    {
        $startedAt = $import->started_at->toImmutable()->setTimezone($this->timezone);

        return [
            'id' => $import->id,
            'startedAt' => __('imports.history.started_at', ['date' => $startedAt->format('j. n. Y'), 'time' => $startedAt->format('H:i')]),
            'trigger' => $import->trigger->value,
            'triggerLabel' => $import->trigger->label(),
            'status' => $import->status->value,
            'statusLabel' => $import->status->label(),
            'reason' => in_array($import->status, [ImportStatus::Error, ImportStatus::Aborted], true) ? $import->error : null,
            'duration' => $this->duration($import),
            'fixturesFound' => $import->fixtures_found,
            'revisionsCount' => (int) $import->getAttribute('revisions_count'),
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
