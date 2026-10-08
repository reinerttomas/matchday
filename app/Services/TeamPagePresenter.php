<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Models\Fixture;
use App\Models\TeamSeason;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type MatchupPart from FixtureFormatter
 *
 * @phpstan-type FixtureProps array{id: int, time: string|null, matchup: list<MatchupPart>, round: string|null, status: string, statusLabel: string|null, score: string|null, venue: string|null}
 * @phpstan-type DateTileProps array{weekday: string, day: string, month: string}
 * @phpstan-type MatchDayProps array{date: string, heading: string, dateTile: DateTileProps, relativeDay: string, venue: string|null, fixtures: list<FixtureProps>}
 */
final readonly class TeamPagePresenter
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
     * Describe the team season for its public team page: its heading, its upcoming fixtures grouped by match day, and where and when its data came from.
     *
     * @return array{name: string, competition: string|null, sourceUrl: string, lastImportedAt: string|null, matchDays: list<MatchDayProps>}
     */
    public function present(TeamSeason $teamSeason): array
    {
        $today = CarbonImmutable::today($this->timezone);

        $fixtures = $teamSeason->fixtures()
            ->chaperone()
            ->with('venue')
            ->where('date', '>=', $today->toDateString())
            ->orderBy('date')
            // Databases disagree on where NULL sorts, so a TBD time is put after the known times explicitly.
            ->orderByRaw('time is null')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        return [
            'name' => $teamSeason->displayName(),
            'competition' => $teamSeason->competition_name,
            'sourceUrl' => $teamSeason->source_url,
            'lastImportedAt' => $this->lastImportedAt($teamSeason),
            'matchDays' => array_values($fixtures
                ->groupBy(fn (Fixture $fixture): string => $fixture->date->toDateString())
                ->map(fn (Collection $matchDayFixtures): array => $this->matchDay($matchDayFixtures, $today))
                ->all()),
        ];
    }

    /**
     * Describe one match day under its date and how far away it is, naming the venue once when all its fixtures share it.
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return MatchDayProps
     */
    private function matchDay(Collection $fixtures, CarbonImmutable $today): array
    {
        $date = $fixtures->firstOrFail()->date;
        $hasSharedVenue = $fixtures->pluck('venue_id')->unique()->count() === 1;

        return [
            'date' => $date->toDateString(),
            'heading' => __($date->year === $today->year ? 'fixtures.team_page.match_day' : 'fixtures.team_page.match_day_with_year', [
                'weekday' => Str::ucfirst($date->isoFormat('dddd')),
                'date' => $date->isoFormat('D. MMMM'),
                'year' => $date->year,
            ]),
            'dateTile' => [
                'weekday' => mb_strtoupper($date->isoFormat('dd')),
                'day' => $date->format('j'),
                'month' => $date->isoFormat('MMM'),
            ],
            'relativeDay' => $this->relativeDay($date, $today),
            'venue' => $hasSharedVenue ? $fixtures->firstOrFail()->venue?->name : null,
            'fixtures' => array_values($fixtures
                ->map(fn (Fixture $fixture): array => $this->fixture($fixture, showVenue: ! $hasSharedVenue))
                ->all()),
        ];
    }

    /**
     * Tell how far away a match day is, such as "Zítra" or "Za 3 dny".
     */
    private function relativeDay(CarbonInterface $date, CarbonImmutable $today): string
    {
        // Today is midnight in Prague while the fixture's date is in the app's timezone, so both are compared as plain dates.
        $days = (int) CarbonImmutable::parse($today->toDateString(), 'UTC')
            ->diffInDays(CarbonImmutable::parse($date->toDateString(), 'UTC'));

        return trans_choice('fixtures.team_page.relative_day', $days, ['count' => $days]);
    }

    /**
     * Describe one fixture as a player scans it: when, who, which round and what state it is in.
     *
     * @return FixtureProps
     */
    private function fixture(Fixture $fixture, bool $showVenue): array
    {
        return [
            'id' => $fixture->id,
            'time' => $fixture->time === null ? null : $this->fixtureFormatter->time($fixture->time),
            'matchup' => $this->fixtureFormatter->matchup($fixture),
            'round' => $fixture->roundLabel(),
            'status' => $fixture->status->value,
            'statusLabel' => $this->statusLabel($fixture),
            'score' => $this->score($fixture),
            'venue' => $showVenue ? $fixture->venue?->name : null,
        ];
    }

    /**
     * Label a postponed or cancelled fixture, so nobody turns up on its date.
     */
    private function statusLabel(Fixture $fixture): ?string
    {
        if (! in_array($fixture->status, [FixtureStatus::Postponed, FixtureStatus::Cancelled], true)) {
            return null;
        }

        return $fixture->status->label();
    }

    /**
     * Show the result of a finished fixture in "Home – Away" order.
     */
    private function score(Fixture $fixture): ?string
    {
        if ($fixture->status !== FixtureStatus::Finished) {
            return null;
        }

        return $this->fixtureFormatter->score($fixture->home_score, $fixture->away_score);
    }

    /**
     * Tell when the team season's fixture list was last imported successfully, in the federation's timezone.
     */
    private function lastImportedAt(TeamSeason $teamSeason): ?string
    {
        $finishedAt = $teamSeason->imports()
            ->where('status', ImportStatus::Ok)
            ->latest('finished_at')
            ->first()
            ?->finished_at
            ?->toImmutable()
            ->setTimezone($this->timezone);

        if ($finishedAt === null) {
            return null;
        }

        return __('imports.team_page.last_import', ['date' => $finishedAt->format('j. n. Y'), 'time' => $finishedAt->format('H:i')]);
    }
}
