<?php

declare(strict_types=1);

namespace App\Enums;

/** What a staff member did in the console, as the audit log records it. */
enum StaffActivityType: string
{
    case PremiumGranted = 'premium_granted';
    case PremiumEnded = 'premium_ended';
    case WorkspaceRestored = 'workspace_restored';
    case PremiumLinkCreated = 'premium_link_created';

    public function label(): string
    {
        return match ($this) {
            self::PremiumGranted => 'Gave Premium',
            self::PremiumEnded => 'Ended Premium',
            self::WorkspaceRestored => 'Restored the workspace',
            self::PremiumLinkCreated => 'Made a Premium link',
        };
    }
}
