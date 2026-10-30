<?php

declare(strict_types=1);

namespace App\Enums;

/** New until it's added to a lead or client, or dismissed. Many forms never need either. */
enum FormResponseStatus: string
{
    case New = 'new';
    case Added = 'added';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Added => 'Added',
            self::Dismissed => 'Dismissed',
        };
    }
}
