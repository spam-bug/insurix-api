<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case Prospect = 'prospect';
    case Contacted = 'contacted';
    case MeetingScheduled = 'meeting_scheduled';
    case Qualified = 'qualified';
    case Lost = 'lost';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Contacted => 'Contacted',
            self::MeetingScheduled => 'Meeting scheduled',
            self::Qualified => 'Qualified',
            self::Lost => 'Lost',
            self::Converted => 'Client',
        };
    }

    /**
     * Statuses an agent can pick by hand. A lead only becomes Converted by being promoted to a client.
     *
     * @return array<int, self>
     */
    public static function manual(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status !== self::Converted));
    }

    /**
     * Still in the pipeline: not a client yet and not lost.
     *
     * @return array<int, self>
     */
    public static function open(): array
    {
        return [self::Prospect, self::Contacted, self::MeetingScheduled, self::Qualified];
    }
}
