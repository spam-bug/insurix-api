<?php

declare(strict_types=1);

namespace App\DataTransferObject\Notifications;

use App\DataTransferObject\Data;
use App\Enums\NotificationType;

class NotificationFiltersData extends Data
{
    public function __construct(
        public readonly bool $unread = false,
        public readonly ?NotificationType $type = null,
        public readonly int $page = 1,
    ) {}
}
