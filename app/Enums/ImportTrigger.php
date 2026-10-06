<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportTrigger: string
{
    case Schedule = 'schedule';
    case Manual = 'manual';

    /**
     * Name what started the import the way the admin pages show it, such as "ručně".
     */
    public function label(): string
    {
        return __("imports.triggers.{$this->value}");
    }
}
