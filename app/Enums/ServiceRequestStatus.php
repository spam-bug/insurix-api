<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingOnInsurer = 'waiting_on_insurer';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::WaitingOnInsurer => 'Waiting on insurer',
            self::Resolved => 'Resolved',
        };
    }

    /**
     * Still needs the agent: everything but resolved.
     *
     * @return array<int, self>
     */
    public static function unresolved(): array
    {
        return [self::Open, self::InProgress, self::WaitingOnInsurer];
    }
}
