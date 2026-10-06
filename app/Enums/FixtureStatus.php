<?php

declare(strict_types=1);

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Postponed = 'postponed';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * Name the status the way the admin pages and the public team page show it, such as "Odloženo".
     */
    public function label(): string
    {
        return __("fixtures.statuses.{$this->value}");
    }
}
