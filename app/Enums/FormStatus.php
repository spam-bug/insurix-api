<?php

declare(strict_types=1);

namespace App\Enums;

/** Draft until first published; a closed form keeps its link but takes no more responses. */
enum FormStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Closed => 'Closed',
        };
    }
}
