<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type ImportFailureProps array{status: string, statusLabel: string, reason: string|null}
 * @phpstan-type LastImportProps array{startedAt: string, failure: ImportFailureProps|null}
 * @phpstan-type TeamSeasonProps array{id: int, name: string, competition: string|null, slug: string, autoImportEnabled: bool, lastImport: LastImportProps|null, calendarUrl: string, publicPageUrl: string, sourceUrl: string}
 */
final readonly class TeamSeasonListPresenter
{
    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
        private CalendarLinks $calendarLinks,
        private FixtureFormatter $fixtureFormatter,
    ) {}

    /**
     * Describe the season's team seasons for the Týmy page, so the administrator sees at a glance what each one is, how its last import went and whether it is imported automatically.
     *
     * @param  Collection<int, TeamSeason>  $teamSeasons
     * @return list<TeamSeasonProps>
     */
    public function present(Collection $teamSeasons): array
    {
        $teamSeasons->loadMissing(['team', 'lastImport']);

        return array_values($teamSeasons
            ->map(fn (TeamSeason $teamSeason): array => $this->teamSeason($teamSeason))
            ->all());
    }

    /**
     * Describe one team season as a row of the list, with the addresses its team actions open or copy.
     *
     * @return TeamSeasonProps
     */
    private function teamSeason(TeamSeason $teamSeason): array
    {
        return [
            'id' => $teamSeason->id,
            'name' => $teamSeason->displayName(),
            'competition' => $teamSeason->competition_name,
            'slug' => $teamSeason->team->slug,
            'autoImportEnabled' => $teamSeason->auto_import_enabled,
            'lastImport' => $teamSeason->lastImport === null ? null : $this->lastImport($teamSeason->lastImport),
            'calendarUrl' => $this->calendarLinks->address($teamSeason->team),
            'publicPageUrl' => route('public-team-page', $teamSeason->team),
            'sourceUrl' => $teamSeason->source_url,
        ];
    }

    /**
     * Tell when the last import started, the way the Importy page does, with its outcome only when it didn't end ok, so a failing team season stands out.
     *
     * @return LastImportProps
     */
    private function lastImport(Import $import): array
    {
        $startedAt = $import->started_at->toImmutable()->setTimezone($this->timezone);

        return [
            'startedAt' => __('imports.history.started_at', ['date' => $this->fixtureFormatter->date($startedAt), 'time' => $startedAt->format('H:i')]),
            'failure' => $import->status === ImportStatus::Ok ? null : [
                'status' => $import->status->value,
                'statusLabel' => $import->status->label(),
                'reason' => $import->error,
            ],
        ];
    }
}
