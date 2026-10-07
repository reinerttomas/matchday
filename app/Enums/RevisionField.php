<?php

declare(strict_types=1);

namespace App\Enums;

enum RevisionField: string
{
    case Date = 'date';
    case Time = 'time';
    case Venue = 'venue';
    case Status = 'status';
    case IsRescheduled = 'is_rescheduled';
    case HomeScore = 'home_score';
    case AwayScore = 'away_score';

    /**
     * Name the revised field the way the admin pages show it, such as "Čas".
     */
    public function label(): string
    {
        return __("revisions.fields.{$this->value}");
    }
}
