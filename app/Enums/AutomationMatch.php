<?php

declare(strict_types=1);

namespace App\Enums;

/** Whether every condition must hold, or one is enough. */
enum AutomationMatch: string
{
    case All = 'all';
    case Any = 'any';
}
