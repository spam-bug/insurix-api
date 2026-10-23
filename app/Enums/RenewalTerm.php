<?php

declare(strict_types=1);

namespace App\Enums;

/** How often a policy renews. Set terms count from the effective date; custom uses a fixed date. */
enum RenewalTerm: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annually',
            self::Custom => 'Custom date',
        };
    }

    /**
     * Months between renewals, or null for a custom date.
     */
    public function months(): ?int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Annual => 12,
            self::Custom => null,
        };
    }

    /**
     * How many days ahead a renewal starts needing attention. Shorter terms get a shorter
     * window, so a monthly renewal isn't flagged all the time.
     */
    public function attentionDays(): int
    {
        return match ($this) {
            self::Monthly => 7,
            self::Quarterly => 14,
            self::Annual, self::Custom => 30,
        };
    }

    /**
     * Whether the agent records an outcome for each renewal. Annual and custom-date terms
     * only: monthly and quarterly ones roll over like payments and aren't tracked one by one.
     */
    public function tracksOutcomes(): bool
    {
        return $this === self::Annual || $this === self::Custom;
    }
}
