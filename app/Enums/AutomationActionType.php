<?php

declare(strict_types=1);

namespace App\Enums;

/** What an automation does for the lead. */
enum AutomationActionType: string
{
    case AddFollowUp = 'add_follow_up';
    case MoveToStage = 'move_to_stage';
    case AddNote = 'add_note';
    case NotifyTeam = 'notify_team';
}
