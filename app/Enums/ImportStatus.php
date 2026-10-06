<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportStatus: string
{
    case Running = 'running';
    case Ok = 'ok';
    case Error = 'error';
    case Aborted = 'aborted';

    /**
     * Name the import's result the way the admin pages show it in its badge, such as "Chyba".
     */
    public function label(): string
    {
        return __("imports.statuses.{$this->value}");
    }
}
