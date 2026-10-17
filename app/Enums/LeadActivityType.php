<?php

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
}
