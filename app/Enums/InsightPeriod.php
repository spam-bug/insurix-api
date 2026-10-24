<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

/** The window Insights measures over. Boundaries use the workspace's timezone. */
enum InsightPeriod: string
{
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';

    /**
     * First and last moment of the period $offset periods before the one containing $today.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonImmutable $today, int $offset = 0): array
    {
        $start = match ($this) {
            self::Month => $today->startOfMonth()->subMonthsNoOverflow($offset),
            self::Quarter => $today->startOfQuarter()->subQuartersNoOverflow($offset),
            self::Year => $today->startOfYear()->subYearsNoOverflow($offset),
        };

        return [$start, match ($this) {
            self::Month => $start->endOfMonth(),
            self::Quarter => $start->endOfQuarter(),
            self::Year => $start->endOfYear(),
        }];
    }

    public function label(CarbonImmutable $start): string
    {
        return match ($this) {
            self::Month => $start->format('F Y'),
            self::Quarter => "Q{$start->quarter} {$start->year}",
            self::Year => (string) $start->year,
        };
    }
}
