<?php

namespace App\Enums;

enum PaymentFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual = 'annual';
    case Single = 'single';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::SemiAnnual => 'Semi-annual',
            self::Annual => 'Annual',
            self::Single => 'Single payment',
        };
    }

    /**
     * How many premiums are paid in a policy year. A single-payment policy counts once.
     */
    public function paymentsPerYear(): int
    {
        return match ($this) {
            self::Monthly => 12,
            self::Quarterly => 4,
            self::SemiAnnual => 2,
            self::Annual, self::Single => 1,
        };
    }

    /**
     * Months between premiums, or null when there is only the one payment.
     */
    public function monthsBetweenPayments(): ?int
    {
        return $this === self::Single ? null : intdiv(12, $this->paymentsPerYear());
    }
}
