<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Lead;

class FormLeadNotification extends WorkspaceNotification
{
    public function __construct(public Lead $lead)
    {
        parent::__construct($lead->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::FormLead;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New lead from your form',
            'body' => "{$this->lead->name} filled in your form.",
            'actor' => null,
            'subject' => ['kind' => 'lead', 'id' => $this->lead->id, 'name' => $this->lead->name],
        ];
    }
}
