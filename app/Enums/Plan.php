<?php

declare(strict_types=1);

namespace App\Enums;

/** What a workspace pays for. Every workspace is Free until payments exist. */
enum Plan: string
{
    case Free = 'free';
    case Premium = 'premium';

    /** How many people (owner, members and pending invites) a workspace on this plan can have. */
    public function seatLimit(): int
    {
        return config("insurix.plans.{$this->value}.seats");
    }

    /** How many forms a workspace on this plan can have; null for no limit. */
    public function formLimit(): ?int
    {
        return config("insurix.plans.{$this->value}.forms");
    }

    public function storageLimitBytes(): int
    {
        return config("insurix.plans.{$this->value}.storage_bytes");
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Premium => 'Premium',
        };
    }
}
