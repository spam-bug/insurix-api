<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Automation;
use App\Models\Lead;

/** An automation's "Notify the team" step: its message about the lead, for everyone in the workspace. */
class AutomationNotification extends WorkspaceNotification
{
    public function __construct(public Automation $automation, public Lead $lead, public string $message)
    {
        parent::__construct($lead->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::Automation;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->automation->name,
            'body' => "{$this->lead->name}: {$this->message}",
            'actor' => null,
            'subject' => ['kind' => 'lead', 'id' => $this->lead->id, 'name' => $this->lead->name],
        ];
    }
}
