<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Imports\ImportTeamSeason;
use App\Enums\ImportTrigger;
use App\Models\TeamSeason;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Signature('fixtures:import {teamSeason : The ID of the team season to import}')]
#[Description('Import the fixture list of a team season from ceskyflorbal.cz')]
final class ImportFixturesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImportTeamSeason $importTeamSeason): int
    {
        $teamSeasonId = $this->argument('teamSeason');
        $teamSeason = TeamSeason::query()->find($teamSeasonId);

        if ($teamSeason === null) {
            error("Team season {$teamSeasonId} not found.");

            return self::FAILURE;
        }

        $import = $importTeamSeason->handle($teamSeason, ImportTrigger::Schedule);

        info("Import finished as {$import->status->value}, {$import->fixtures_found} fixtures found.");

        return self::SUCCESS;
    }
}
