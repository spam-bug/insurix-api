<?php

declare(strict_types=1);

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

    /** The insurer turned the application down. */
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InForce => 'In force',
            self::Lapsed => 'Lapsed',
            self::Cancelled => 'Cancelled',
            self::Declined => 'Declined',
        };
    }

    /**
     * The statuses a bulk change can move between. Pending and declined go through the application steps.
     *
     * @return array<int, self>
     */
    public static function bulkSettable(): array
    {
        return [self::InForce, self::Lapsed, self::Cancelled];
    }
}
