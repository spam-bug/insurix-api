<?php

declare(strict_types=1);

namespace App\Enums;

/** Where a user can choose to get a kind of notification. */
enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
}
