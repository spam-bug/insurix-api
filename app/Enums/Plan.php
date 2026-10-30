<?php

declare(strict_types=1);

namespace App\Enums;

/** What a workspace pays for. Every workspace is Free until payments exist. */
enum Plan: string
{
    case Free = 'free';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Premium => 'Premium',
        };
    }
}
