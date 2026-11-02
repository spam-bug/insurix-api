<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterval;

/** How a wait step counts its time. */
enum AutomationWaitUnit: string
{
    case Hours = 'hours';
    case Days = 'days';

    /** The longest wait, either way about a month. */
    public function maxAmount(): int
    {
        return match ($this) {
            self::Hours => 720,
            self::Days => 30,
        };
    }

    public function interval(int $amount): CarbonInterval
    {
        return match ($this) {
            self::Hours => CarbonInterval::hours($amount),
            self::Days => CarbonInterval::days($amount),
        };
    }
}
