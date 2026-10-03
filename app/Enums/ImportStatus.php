<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportStatus: string
{
    case Running = 'running';
    case Ok = 'ok';
    case Error = 'error';
    case Aborted = 'aborted';
}
