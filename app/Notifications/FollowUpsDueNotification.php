<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class FollowUpsDueNotification extends WorkspaceNotification
{
    public function __construct(string $workspaceId, public int $due, public int $overdue)
    {
        parent::__construct($workspaceId);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::FollowUpsDue;
    }

    public function toArray(object $notifiable): array
    {
        $count = $this->due + $this->overdue;

        return [
            'title' => $count === 1 ? '1 follow-up for today' : "{$count} follow-ups for today",
            'body' => $this->overdue === 0
                ? 'All due today. Your Today list has them in order.'
                : "{$this->overdue} of them overdue. Your Today list has them in order.",
            'actor' => null,
            'subject' => null,
        ];
    }
}
