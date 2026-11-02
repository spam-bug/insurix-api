<?php

declare(strict_types=1);

namespace App\Enums;

/** Where a run of an automation stands: done, paused at a wait, or stopped before the end. */
enum AutomationRunStatus: string
{
    case Completed = 'completed';
    case Waiting = 'waiting';
    case Stopped = 'stopped';
}
