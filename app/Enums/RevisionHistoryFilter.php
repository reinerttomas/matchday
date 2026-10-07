<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which imports with revisions the Změny page lists: the ones whose change summary still waits to be sent to the team, or every one.
 */
enum RevisionHistoryFilter: string
{
    case Unsent = 'unsent';
    case All = 'all';

    /**
     * Read the filter from the page's query string, or null for a missing or unknown value, so the page can open on what there is to send.
     */
    public static function tryFromQuery(mixed $value): ?self
    {
        return is_string($value) ? self::tryFrom($value) : null;
    }
}
