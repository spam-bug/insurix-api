<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\Workspace;

class MemberJoinedNotification extends WorkspaceNotification
{
    public function __construct(public Workspace $workspace, public User $member)
    {
        parent::__construct($workspace->id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::MemberJoined;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->member->name} joined {$this->workspace->name}",
            'body' => 'They accepted your invite and can work with your leads and clients now.',
            'actor' => $this->actor($this->member),
            'subject' => null,
        ];
    }
}
