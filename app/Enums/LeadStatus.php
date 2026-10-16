<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Prospect = 'prospect';
    case Contacted = 'contacted';
    case MeetingScheduled = 'meeting_scheduled';
    case Qualified = 'qualified';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Contacted => 'Contacted',
            self::MeetingScheduled => 'Meeting scheduled',
            self::Qualified => 'Qualified',
            self::Lost => 'Lost',
        };
    }
}
