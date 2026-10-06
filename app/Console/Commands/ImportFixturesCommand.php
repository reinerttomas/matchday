<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Imports\ImportTeamSeason;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\TeamSeason;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

#[Signature('fixtures:import {teamSeason? : The ID of the team season to import; without it, every team season of the current season with auto import enabled is imported}')]
#[Description('Import the fixture list of a team season, or of every team season of the current season with auto import enabled, from ceskyflorbal.cz')]
final class ImportFixturesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImportTeamSeason $importTeamSeason): int
    {
        $teamSeasonId = $this->argument('teamSeason');

        if ($teamSeasonId === null) {
            return $this->importAutoImportedTeamSeasons($importTeamSeason);
        }

        $teamSeason = TeamSeason::query()->find($teamSeasonId);

        if ($teamSeason === null) {
            error("Team season {$teamSeasonId} not found.");

            return self::FAILURE;
        }

        $import = $importTeamSeason->handle($teamSeason, ImportTrigger::Manual);
        $this->printOutcome($import);

        return $this->isFailure($import) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Import each team season of the current season with auto import enabled, one after another, so ceskyflorbal.cz sees a single client's pace.
     */
    private function importAutoImportedTeamSeasons(ImportTeamSeason $importTeamSeason): int
    {
        $teamSeasons = TeamSeason::query()
            ->where('auto_import_enabled', true)
            ->whereRelation('season', 'is_current', true)
            ->orderBy('id')
            ->get();
        $ok = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($teamSeasons as $teamSeason) {
            $prefix = "Team season {$teamSeason->id}: ";

            try {
                $import = $importTeamSeason->handle($teamSeason, ImportTrigger::Schedule);
            } catch (Throwable $exception) {
                // Any import that was started has already ended as error; the remaining team seasons are still imported.
                report($exception);
                error("{$prefix}Import failed: {$exception->getMessage()}");
                $failed++;

                continue;
            }

            $this->printOutcome($import, $prefix);

            match (true) {
                $import === null => $skipped++,
                $this->isFailure($import) => $failed++,
                default => $ok++,
            };
        }

        info("{$teamSeasons->count()} team seasons: {$ok} ok, {$failed} failed, {$skipped} skipped.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Print how the import of a team season ended, or that it was skipped.
     */
    private function printOutcome(?Import $import, string $prefix = ''): void
    {
        if ($import === null) {
            warning("{$prefix}Import skipped, another import of the team season is running.");
        } elseif ($this->isFailure($import)) {
            error("{$prefix}Import finished as {$import->status->value}: {$import->error}");
        } else {
            info("{$prefix}Import finished as {$import->status->value}, {$import->fixtures_found} fixtures found.");
        }
    }

    /**
     * Determine whether the import ended as error or aborted; a skipped import has not failed.
     */
    private function isFailure(?Import $import): bool
    {
        return $import !== null && $import->status !== ImportStatus::Ok;
    }
}
