<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Channels\WorkspaceDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something in a workspace a user should know about: kept for the bell, pushed to the
 * user's open tabs and emailed, unless the user turned that kind off. Each kind says what it's
 * about; the app builds the link and actions.
 */
abstract class WorkspaceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $workspaceId) {}

    abstract public function notificationType(): NotificationType;

    /**
     * @return array{title: string, body: string, actor: array{id: string, name: string, avatar_url: string|null}|null, subject: array{kind: string, id: string, name: string}|null}
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->notificationType()->isOnFor($notifiable) ? [WorkspaceDatabaseChannel::class, 'broadcast', 'mail'] : [];
    }

    /** The button opens the app, which finds the record from the type and subject (`/w/{id}/open`). */
    public function toMail(object $notifiable): MailMessage
    {
        ['title' => $title, 'body' => $body, 'subject' => $subject] = $this->toArray($notifiable);
        $workspace = Workspace::findOrFail($this->workspaceId);
        $frontendUrl = config('app.frontend_url');
        $query = http_build_query(['type' => $this->notificationType()->value, 'kind' => $subject['kind'] ?? null, 'id' => $subject['id'] ?? null]);

        return (new MailMessage)
            ->subject("{$title} · {$workspace->name}")
            ->greeting($title)
            ->line($body)
            ->action('Open in Insurix', "{$frontendUrl}/w/{$workspace->id}/open?{$query}")
            ->line("You get this email for {$workspace->name}. Choose which emails you get in [your notification settings]({$frontendUrl}/w/{$workspace->id}/settings/notifications).");
    }

    public function databaseType(object $notifiable): string
    {
        return $this->notificationType()->value;
    }

    public function broadcastType(): string
    {
        return $this->notificationType()->value;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->toArray($notifiable),
            'workspace_id' => $this->workspaceId,
            'label' => $this->notificationType()->label(),
        ]);
    }

    /**
     * @return array{id: string, name: string, avatar_url: string|null}
     */
    protected function actor(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatarUrl()];
    }
}
