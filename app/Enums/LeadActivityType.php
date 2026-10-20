<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadActivityType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case MeetingScheduled = 'meeting_scheduled';
    case Note = 'note';
    case Promoted = 'promoted';
    case FollowUpCompleted = 'follow_up_completed';

    /**
     * What counts as the agent being in touch with a lead: a note, a meeting, a status change or
     * a completed follow-up. Edits to the lead's details don't count.
     *
     * @return array<int, self>
     */
    public static function touches(): array
    {
        return [self::Note, self::MeetingScheduled, self::StatusChanged, self::FollowUpCompleted];
    }
}
