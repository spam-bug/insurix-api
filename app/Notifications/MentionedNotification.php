<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Actions\Mentions\RecordMentions;
use App\Enums\NotificationType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Str;

class MentionedNotification extends WorkspaceNotification
{
    public function __construct(public Lead $lead, public LeadActivity $note, public User $author)
    {
        parent::__construct($lead->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::Mention;
    }

    public function toArray(object $notifiable): array
    {
        $client = $this->lead->client;

        return [
            'title' => "{$this->author->name} mentioned you",
            'body' => Str::limit(preg_replace(RecordMentions::PATTERN, '@$1', (string) $this->note->body), 160),
            'actor' => $this->actor($this->author),
            'subject' => $client
                ? ['kind' => 'client', 'id' => $client->id, 'name' => $client->name]
                : ['kind' => 'lead', 'id' => $this->lead->id, 'name' => $this->lead->name],
        ];
    }
}
