<?php

declare(strict_types=1);

namespace App\DataTransferObject\Account;

use App\DataTransferObject\Data;
use App\Enums\NotificationChannel;
use App\Enums\NotificationType;

class NotificationPreferenceData extends Data
{
    public function __construct(
        public readonly NotificationType $type,
        public readonly NotificationChannel $channel,
        public readonly bool $enabled,
    ) {}
}
