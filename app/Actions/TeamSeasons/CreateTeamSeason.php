<?php

declare(strict_types=1);

namespace App\Actions\TeamSeasons;

use App\Actions\Imports\QueueManualImport;
use App\Jobs\SubscribeAdministratorsToTeams;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Support\Facades\DB;

final readonly class CreateTeamSeason
{
    /**
     * Create a new instance.
     */
    public function __construct(private QueueManualImport $queueManualImport) {}

    /**
     * Start tracking the team in the season, and queue the team season's first import, which fills in its competition. A brand-new team gets every administrator subscribed to it.
     *
     * A brand-new team is passed unsaved and saved together with its first team season, so a team never exists without one. A team carried over from a previous season keeps its name and slug, and with it its calendar address.
     *
     * @param  array{external_id: int}  $attributes
     */
    public function handle(Team $team, Season $season, array $attributes): TeamSeason
    {
        $isNewTeam = ! $team->exists;

        $teamSeason = DB::transaction(function () use ($team, $season, $attributes): TeamSeason {
            $team->save();

            return $team->teamSeasons()->create([...$attributes, 'season_id' => $season->id]);
        });

        if ($isNewTeam) {
            dispatch(new SubscribeAdministratorsToTeams);
        }

        $this->queueManualImport->handle($teamSeason);

        return $teamSeason;
    }
}
