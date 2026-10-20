<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a pending application stands, as the insurer last told the agent. Approval puts the
 * policy in force and a decline marks it Declined, so neither is a stage.
 */
enum ApplicationStage: string
{
    case Submitted = 'submitted';
    case Underwriting = 'underwriting';
    case RequirementsNeeded = 'requirements_needed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Underwriting => 'Underwriting',
            self::RequirementsNeeded => 'Requirements needed',
        };
    }
}
