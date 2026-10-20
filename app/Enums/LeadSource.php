<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSource: string
{
    case Manual = 'manual';
    case Referral = 'referral';
    case ShareableForm = 'shareable_form';
    case SocialMedia = 'social_media';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual entry',
            self::Referral => 'Referral',
            self::ShareableForm => 'Shareable form',
            self::SocialMedia => 'Social media',
            self::Other => 'Other',
        };
    }
}
