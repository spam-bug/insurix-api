<?php

declare(strict_types=1);

namespace App\Enums;

/** What an automation does for the lead. */
enum AutomationActionType: string
{
    case AddFollowUp = 'add_follow_up';
    case DraftMessage = 'draft_message';
    case SendForm = 'send_form';
    case MoveToStage = 'move_to_stage';
    case AddNote = 'add_note';
    case NotifyTeam = 'notify_team';
    case Wait = 'wait';
}
