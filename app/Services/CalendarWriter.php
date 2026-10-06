<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Config;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

final readonly class CalendarWriter
{
    /**
     * How long the event of a fixture with a known start time blocks the calendar.
     */
    private const int EVENT_MINUTES = 60;

    /**
     * How often subscribed calendar apps that honour the hint download the feed again.
     */
    private const int REFRESH_MINUTES = 60;

    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.url')]
        private string $ceskyflorbalUrl,
        #[Config('services.ceskyflorbal.timezone')]
        private string $timezone,
    ) {}

    /**
     * Write the team's calendar feed: one event per fixture of its team season in the current season, or no events when the team has none.
     */
    public function write(Team $team): string
    {
        $teamSeason = $team->currentTeamSeason;

        $calendar = Calendar::create($team->calendarName())
            ->refreshInterval(self::REFRESH_MINUTES);

        if ($teamSeason === null) {
            return $calendar->get();
        }

        $fixtures = $teamSeason->fixtures()
            ->chaperone()
            ->with('venue')
            ->orderBy('date')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        return $calendar->event($fixtures->map($this->event(...))->all())->get();
    }

    /**
     * Describe a fixture as a calendar event that keeps its UID across requests and imports, so a revised fixture replaces its event instead of adding another.
     */
    private function event(Fixture $fixture): Event
    {
        $matchDetailUrl = "{$this->ceskyflorbalUrl}/match/detail/default/{$fixture->external_id}";

        $event = Event::create($this->title($fixture))
            ->uniqueIdentifier("matchday-fixture-{$fixture->id}")
            ->sequence($fixture->sequence)
            ->description($this->description($fixture, $matchDetailUrl))
            ->url($matchDetailUrl);

        if ($fixture->time === null) {
            // Until the federation sets a start time, the fixture shows on its date without a made-up time.
            $event->startsAt($fixture->date, withTime: false)->fullDay()->withoutTimezone();
        } else {
            $startsAt = CarbonImmutable::parse("{$fixture->date->toDateString()} {$fixture->time}", $this->timezone);
            $event->startsAt($startsAt)->endsAt($startsAt->addMinutes(self::EVENT_MINUTES));
        }

        // Only a fixture that will be played at a known time blocks the player's calendar.
        if ($fixture->time === null || in_array($fixture->status, [FixtureStatus::Postponed, FixtureStatus::Cancelled], true)) {
            $event->transparent();
        }

        // Subscribed calendars mark the event cancelled only while it stays in the feed; dropping it would remove it silently.
        if ($fixture->status === FixtureStatus::Cancelled) {
            $event->status(EventStatus::Cancelled);
        }

        $location = $this->location($fixture);

        if ($location !== null) {
            $event->address($location);
        }

        return $event;
    }

    /**
     * Name the fixture's sides in "Home – Away" order, marked with what a player needs to know about its state.
     */
    private function title(Fixture $fixture): string
    {
        $sides = ['home' => $fixture->homeTeamName(), 'away' => $fixture->awayTeamName()];

        // A postponed or cancelled fixture is marked even without a start time, since nobody should turn up on that date at all.
        return match (true) {
            $fixture->status === FixtureStatus::Postponed => __('fixtures.calendar.titles.postponed', $sides),
            $fixture->status === FixtureStatus::Cancelled => __('fixtures.calendar.titles.cancelled', $sides),
            $fixture->status === FixtureStatus::Finished && $fixture->home_score !== null && $fixture->away_score !== null => __('fixtures.calendar.titles.finished', [
                ...$sides,
                'home_score' => $fixture->home_score,
                'away_score' => $fixture->away_score,
            ]),
            $fixture->status === FixtureStatus::Scheduled && $fixture->time === null => __('fixtures.calendar.titles.tbd_time', $sides),
            default => __('fixtures.calendar.titles.default', $sides),
        };
    }

    /**
     * Describe where the fixture is played: the venue's name, followed by its address when it has one.
     */
    private function location(Fixture $fixture): ?string
    {
        $venue = $fixture->venue;

        if ($venue?->address === null) {
            return $venue?->name;
        }

        return __('fixtures.calendar.location', ['venue' => $venue->name, 'address' => $venue->address]);
    }

    /**
     * Describe the fixture's competition and round, with a link to its match detail page on ceskyflorbal.cz.
     */
    private function description(Fixture $fixture, string $matchDetailUrl): string
    {
        return implode("\n", array_filter([
            $fixture->teamSeason->competition_name,
            $fixture->roundLabel(),
            __('fixtures.calendar.match_detail', ['url' => $matchDetailUrl]),
        ]));
    }
}
