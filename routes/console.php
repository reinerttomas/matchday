<?php

declare(strict_types=1);

use App\Console\Commands\ImportFixturesCommand;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Facades\Schedule;

// The overlap lock expires before the next run, so a killed scheduler holds back imports for one run instead of the default 24 hours.
Schedule::command(ImportFixturesCommand::class)->everyFourHours()->withoutOverlapping(expiresAt: 230);

Schedule::command(PruneCommand::class)->daily();
