<?php

declare(strict_types=1);

namespace App\Enums;

/** What a workspace pays for. Every workspace is Free until payments exist. */
enum Plan: string
{
    case Free = 'free';
    case Premium = 'premium';

    /** How many forms a workspace on this plan can have; null for no limit. */
    public function formLimit(): ?int
    {
        return $this === self::Free ? config('insurix.free_form_limit') : null;
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Premium => 'Premium',
        };
    }
}
