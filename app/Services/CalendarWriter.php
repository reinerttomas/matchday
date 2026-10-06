<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fixture;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Config;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

final readonly class CalendarWriter
{
    /**
     * Fixture dates and times are Prague wall-clock values as the federation publishes them.
     */
    private const string TIMEZONE = 'Europe/Prague';

    /**
     * How long the event of a fixture with a known start time blocks the calendar.
     */
    private const int EVENT_MINUTES = 55;

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
    ) {}

    /**
     * Write the team's calendar feed: one event per fixture of its team season in the current season, or no events when the team has none.
     */
    public function write(Team $team): string
    {
        $teamSeason = $team->teamSeasons()
            ->chaperone()
            ->whereRelation('season', 'is_current', true)
            ->first();

        $calendar = Calendar::create($teamSeason?->displayName() ?? $team->slug)
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

        $event = Event::create(__('fixtures.calendar.title', ['home' => $fixture->homeTeamName(), 'away' => $fixture->awayTeamName()]))
            ->uniqueIdentifier("matchday-fixture-{$fixture->id}")
            ->sequence($fixture->sequence)
            ->description($this->description($fixture, $matchDetailUrl))
            ->url($matchDetailUrl);

        if ($fixture->time === null) {
            // Until the federation sets a start time, the fixture shows on its date without a made-up time.
            $event->startsAt($fixture->date, withTime: false)->fullDay()->withoutTimezone();
        } else {
            $startsAt = CarbonImmutable::parse("{$fixture->date->toDateString()} {$fixture->time}", self::TIMEZONE);
            $event->startsAt($startsAt)->endsAt($startsAt->addMinutes(self::EVENT_MINUTES));
        }

        $location = $this->location($fixture);

        if ($location !== null) {
            $event->address($location);
        }

        return $event;
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
            $fixture->round === null ? null : __('fixtures.calendar.round', ['round' => $fixture->round]),
            __('fixtures.calendar.match_detail', ['url' => $matchDetailUrl]),
        ]));
    }
}
