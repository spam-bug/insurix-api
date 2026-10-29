<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Actions\Mentions\RecordMentions;
use App\Enums\NotificationType;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Str;

class MentionedNotification extends WorkspaceNotification
{
    public function __construct(public Note $note, public User $author)
    {
        parent::__construct($note->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::Mention;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->author->name} mentioned you",
            'body' => Str::limit(preg_replace(RecordMentions::PATTERN, '@$1', $this->note->body), 160),
            'actor' => $this->actor($this->author),
            'subject' => $this->subject(),
        ];
    }

    /**
     * What the note is about, most specific first; a note about nothing points to itself.
     *
     * @return array{kind: string, id: string, name: string}
     */
    private function subject(): array
    {
        $note = $this->note;

        return match (true) {
            $note->policy_id !== null => ['kind' => 'policy', 'id' => $note->policy_id, 'name' => $note->policy->product_name],
            $note->client_id !== null => ['kind' => 'client', 'id' => $note->client_id, 'name' => $note->client->name],
            $note->lead_id !== null => ['kind' => 'lead', 'id' => $note->lead_id, 'name' => $note->lead->name],
            default => ['kind' => 'note', 'id' => $note->id, 'name' => Str::limit($note->body, 40)],
        };
    }
}
