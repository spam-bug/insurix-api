<?php

declare(strict_types=1);

namespace App\Enums;

/** What a client asked their agent for. Claims also track the amount and the insurer's reference. */
enum ServiceRequestType: string
{
    case Inquiry = 'inquiry';
    case PolicyChange = 'policy_change';
    case Claim = 'claim';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Inquiry => 'Inquiry',
            self::PolicyChange => 'Policy change',
            self::Claim => 'Claim',
            self::Other => 'Other',
        };
    }
}
