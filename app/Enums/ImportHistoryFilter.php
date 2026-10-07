<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which imports the Importy page lists: every one, the ones that recorded revisions, or the ones that ended as error or aborted.
 */
enum ImportHistoryFilter: string
{
    case All = 'all';
    case Revised = 'revised';
    case Failed = 'failed';

    /**
     * Read the filter from the page's query string, falling back to every import for a missing or unknown value.
     */
    public static function fromQuery(mixed $value): self
    {
        return (is_string($value) ? self::tryFrom($value) : null) ?? self::All;
    }
}
