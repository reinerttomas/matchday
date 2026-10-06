<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Services\CalendarLinks;
use App\Services\TeamPagePresenter;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PublicTeamPageController
{
    /**
     * Show the team's public page with the upcoming fixtures of its team season in the current season and the links to subscribe to its calendar, which players open from a shared link or QR code.
     */
    public function __invoke(Team $team, TeamPagePresenter $teamPagePresenter, CalendarLinks $calendarLinks): Response
    {
        $teamSeason = $team->currentTeamSeason;

        return Inertia::render('public/team', [
            'season' => Season::query()->where('is_current', true)->value('name'),
            'teamSeason' => $teamSeason === null ? null : $teamPagePresenter->present($teamSeason),
            'calendar' => $calendarLinks->for($team),
            'pageUrl' => route('public-team-page', $team),
        ]);
    }
}
