<?php

namespace App\Enums;

/** What happened at a renewal date, as the agent records it. */
enum RenewalOutcome: string
{
    case Renewed = 'renewed';
    case Lapsed = 'lapsed';
    case NotRenewing = 'not_renewing';

    public function label(): string
    {
        return match ($this) {
            self::Renewed => 'Renewed',
            self::Lapsed => 'Lapsed',
            self::NotRenewing => 'Not renewing',
        };
    }

    /**
     * The policy's status once this is recorded.
     */
    public function policyStatus(): PolicyStatus
    {
        return match ($this) {
            self::Renewed => PolicyStatus::InForce,
            self::Lapsed => PolicyStatus::Lapsed,
            self::NotRenewing => PolicyStatus::Cancelled,
        };
    }
}
