<?php

declare(strict_types=1);

namespace App\Enums;

/** What starts an automation for a lead. */
enum AutomationTrigger: string
{
    case LeadCreated = 'lead_created';
    case LeadStageChanged = 'lead_stage_changed';
    case LeadIdle = 'lead_idle';

    public function label(): string
    {
        return match ($this) {
            self::LeadCreated => 'A lead is added',
            self::LeadStageChanged => 'A lead moves to a stage',
            self::LeadIdle => 'A lead has no activity for a while',
        };
    }
}
