<?php

namespace App\Enums;

enum PolicyType: string
{
    case Life = 'life';
    case Health = 'health';
    case Auto = 'auto';
    case Property = 'property';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Life => 'Life',
            self::Health => 'Health',
            self::Auto => 'Auto',
            self::Property => 'Property',
            self::Other => 'Other',
        };
    }
}
