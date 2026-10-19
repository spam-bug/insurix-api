<?php

namespace App\Enums;

/** Entries on a policy's timeline. */
enum PolicyEventType: string
{
    case Recorded = 'recorded';
    case StageChanged = 'stage_changed';
    case Approved = 'approved';
    case Declined = 'declined';
    case RequirementAdded = 'requirement_added';
    case RequirementReceived = 'requirement_received';
    case RenewalRecorded = 'renewal_recorded';
}
