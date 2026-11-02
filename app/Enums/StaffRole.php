<?php

declare(strict_types=1);

namespace App\Enums;

/** What a staff member may do in the console: admins change things, support looks things up. */
enum StaffRole: string
{
    case Admin = 'admin';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Support => 'Support',
        };
    }
}
