<?php

declare(strict_types=1);

namespace App\Enums;

/** Whether a premium link can still be claimed. */
enum PremiumLinkState: string
{
    case Available = 'available';
    case Expired = 'expired';
    case UsedUp = 'used_up';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Expired => 'This link has expired.',
            self::UsedUp => 'This link has already been used.',
        };
    }
}
