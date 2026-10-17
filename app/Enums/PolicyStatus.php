<?php

namespace App\Enums;

/**
 * Where a policy stands, as the agent records it. There is no insurer integration, so these
 * are updated by hand from what the insurer reports.
 */
enum PolicyStatus: string
{
    /** Application submitted, waiting on underwriting or approval. */
    case Pending = 'pending';
    case InForce = 'in_force';
    case Lapsed = 'lapsed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InForce => 'In force',
            self::Lapsed => 'Lapsed',
            self::Cancelled => 'Cancelled',
        };
    }
}
