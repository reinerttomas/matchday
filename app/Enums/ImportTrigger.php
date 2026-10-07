<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportTrigger: string
{
    case Schedule = 'schedule';
    case Manual = 'manual';
}
