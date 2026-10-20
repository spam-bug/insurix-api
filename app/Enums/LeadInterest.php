<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadInterest: string
{
    case Life = 'life';
    case Health = 'health';
    case Auto = 'auto';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Life => 'Life',
            self::Health => 'Health',
            self::Auto => 'Auto',
            self::Property => 'Property',
        };
    }
}
