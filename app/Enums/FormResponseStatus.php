<?php

declare(strict_types=1);

namespace App\Enums;

/** Whether a response has become a lead or client yet. */
enum FormResponseStatus: string
{
    case NeedsReview = 'needs_review';
    case Added = 'added';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs review',
            self::Added => 'Added',
            self::Dismissed => 'Dismissed',
        };
    }
}
