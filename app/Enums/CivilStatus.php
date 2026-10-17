<?php

namespace App\Enums;

enum CivilStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Separated = 'separated';
    case Widowed = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Married => 'Married',
            self::Separated => 'Separated',
            self::Widowed => 'Widowed',
        };
    }
}
