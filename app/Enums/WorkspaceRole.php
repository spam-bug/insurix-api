<?php

declare(strict_types=1);

namespace App\Enums;

/** The owner is the agent who runs the business; members are the people helping them. */
enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Member => 'Member',
        };
    }
}
