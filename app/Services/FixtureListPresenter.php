<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Models\Fixture;
use App\Models\Revision;
use App\Models\TeamSeason;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type MatchupPart from FixtureFormatter
 *
 * @phpstan-type BadgeProps array{kind: string, label: string}
 * @phpstan-type FixtureProps array{id: int, day: string, time: string|null, matchup: list<MatchupPart>, venue: string|null, status: string, badges: list<BadgeProps>, score: string|null, revisions: list<string>}
 * @phpstan-type MonthProps array{month: string, heading: string, fixtures: list<FixtureProps>}
 * @phpstan-type ImportFailureProps array{status: string, reason: string|null}
 * @phpstan-type FixtureListProps array{period: string, upcomingCount: int, seasonCount: int, isImported: bool, lastImportFailure: ImportFailureProps|null, months: list<MonthProps>}
 */
final readonly class FixtureListPresenter
{
    /**
     * How many days a revision keeps its fixture marked as changed.
     */
    private const int RECENT_REVISION_DAYS = 7;

    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
        private FixtureFormatter $fixtureFormatter,
    ) {}

    /**
     * Describe the team season's fixture list as the app has stored it, laid out like the federation's website, so the administrator can compare the two and spot what changed recently.
     *
     * @return FixtureListProps
     */
    public function present(TeamSeason $teamSeason, bool $showsWholeSeason): array
    {
        $today = CarbonImmutable::today($this->timezone)->toDateString();

        $fixtures = $teamSeason->fixtures()
            ->chaperone()
            ->with([
                'venue',
                'revisions' => fn (Relation $revisions): Relation => $revisions
                    ->where('created_at', '>=', now()->subDays(self::RECENT_REVISION_DAYS))
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->orderBy('date')
            // Databases disagree on where NULL sorts, so a TBD time is put after the known times explicitly.
            ->orderByRaw('time is null')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        $upcomingFixtures = $fixtures->filter(fn (Fixture $fixture): bool => $fixture->date->toDateString() >= $today);

        return [
            'period' => $showsWholeSeason ? 'season' : 'upcoming',
            'upcomingCount' => $upcomingFixtures->count(),
            'seasonCount' => $fixtures->count(),
            'isImported' => $teamSeason->imports()->where('status', ImportStatus::Ok)->exists(),
            'lastImportFailure' => $this->lastImportFailure($teamSeason),
            'months' => array_values(($showsWholeSeason ? $fixtures : $upcomingFixtures)
                ->groupBy(fn (Fixture $fixture): string => $fixture->date->format('Y-m'))
                ->map(fn (Collection $monthFixtures): array => $this->month($monthFixtures))
                ->all()),
        ];
    }

    /**
     * Describe the last finished import when it ended as error or aborted, so the administrator notices that the stored fixture list may be out of date.
     *
     * @return ImportFailureProps|null
     */
    private function lastImportFailure(TeamSeason $teamSeason): ?array
    {
        // A running import hasn't ended yet, so the warning about the previous one stays until it does.
        $lastImport = $teamSeason->imports()
            ->whereNot('status', ImportStatus::Running)
            ->latest('started_at')
            ->latest('id')
            ->first();

        if ($lastImport === null || ! in_array($lastImport->status, [ImportStatus::Error, ImportStatus::Aborted], true)) {
            return null;
        }

        return [
            'status' => $lastImport->status->value,
            'reason' => $lastImport->error,
        ];
    }

    /**
     * Describe one month of the fixture list under its heading, such as "Říjen 2026".
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return MonthProps
     */
    private function month(Collection $fixtures): array
    {
        $date = $fixtures->firstOrFail()->date;

        return [
            'month' => $date->format('Y-m'),
            'heading' => __('fixtures.list.month', ['month' => Str::ucfirst($date->isoFormat('MMMM')), 'year' => $date->year]),
            'fixtures' => array_values($fixtures->map(fn (Fixture $fixture): array => $this->fixture($fixture))->all()),
        ];
    }

    /**
     * Describe one fixture as a row of the fixture list.
     *
     * @return FixtureProps
     */
    private function fixture(Fixture $fixture): array
    {
        return [
            'id' => $fixture->id,
            'day' => $this->fixtureFormatter->day($fixture->date),
            'time' => $fixture->time === null ? null : $this->fixtureFormatter->time($fixture->time),
            'matchup' => $this->fixtureFormatter->matchup($fixture),
            'venue' => $fixture->venue?->name,
            'status' => $fixture->status->value,
            'badges' => $this->badges($fixture),
            'score' => $fixture->status === FixtureStatus::Finished ? $this->fixtureFormatter->score($fixture->home_score, $fixture->away_score) : null,
            'revisions' => array_values($fixture->revisions->map(fn (Revision $revision): string => $this->fixtureFormatter->revision($revision))->all()),
        ];
    }

    /**
     * Pick the badges that set the fixture apart; an ordinary scheduled fixture gets none, so badges carry meaning.
     *
     * @return list<BadgeProps>
     */
    private function badges(Fixture $fixture): array
    {
        return array_values(array_filter([
            $fixture->is_rescheduled ? $this->badge('rescheduled', __('fixtures.list.badges.rescheduled')) : null,
            $this->statusBadge($fixture),
            $fixture->revisions->isNotEmpty() ? $this->badge('revised', __('fixtures.list.badges.revised')) : null,
        ]));
    }

    /**
     * Mark a postponed or cancelled fixture by its status, and a finished one by its result from our team's point of view.
     *
     * @return BadgeProps|null
     */
    private function statusBadge(Fixture $fixture): ?array
    {
        return match ($fixture->status) {
            FixtureStatus::Scheduled => null,
            FixtureStatus::Postponed, FixtureStatus::Cancelled => $this->badge($fixture->status->value, $fixture->status->label()),
            FixtureStatus::Finished => $this->resultBadge($fixture),
        };
    }

    /**
     * Mark a finished fixture as "Výhra", "Prohra" or "Remíza" for our team, or just as finished while its score is unknown.
     *
     * @return BadgeProps
     */
    private function resultBadge(Fixture $fixture): array
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return $this->badge($fixture->status->value, $fixture->status->label());
        }

        $ourScore = $fixture->is_home ? $fixture->home_score : $fixture->away_score;
        $opponentScore = $fixture->is_home ? $fixture->away_score : $fixture->home_score;

        $result = match ($ourScore <=> $opponentScore) {
            1 => 'win',
            -1 => 'loss',
            default => 'draw',
        };

        return $this->badge($result, __("fixtures.list.badges.{$result}"));
    }

    /**
     * Describe a badge by its kind, which the page styles, and its label.
     *
     * @return BadgeProps
     */
    private function badge(string $kind, string $label): array
    {
        return ['kind' => $kind, 'label' => $label];
    }
}
