<?php

declare(strict_types=1);

namespace App\Enums;

/** The tabs of the requests list. */
enum ServiceRequestView: string
{
    case Unresolved = 'unresolved';
    case Overdue = 'overdue';
    case WaitingOnInsurer = 'waiting_on_insurer';
    case Resolved = 'resolved';
}
