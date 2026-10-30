<?php

declare(strict_types=1);

namespace App\Enums;

/** Who may use a feature: nobody, Premium workspaces only, or every workspace. */
enum FeatureAccess: string
{
    case Off = 'off';
    case Premium = 'premium';
    case Free = 'free';

    public function allows(Plan $plan): bool
    {
        return match ($this) {
            self::Off => false,
            self::Premium => $plan === Plan::Premium,
            self::Free => true,
        };
    }
}
