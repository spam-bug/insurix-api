<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Support\Number;

class StorageAlmostFullNotification extends WorkspaceNotification
{
    public function __construct(string $workspaceId, public int $used, public int $limit)
    {
        parent::__construct($workspaceId);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::StorageAlmostFull;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document storage is almost full',
            'body' => Number::fileSize($this->used, 1).' of '.Number::fileSize($this->limit).' used. Delete files you no longer need to make room.',
            'actor' => null,
            'subject' => null,
        ];
    }
}
