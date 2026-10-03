<?php

declare(strict_types=1);

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Postponed = 'postponed';
    case Finished = 'finished';
    case Cancelled = 'cancelled';
}
