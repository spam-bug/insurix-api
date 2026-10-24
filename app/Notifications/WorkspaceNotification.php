<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\Channels\WorkspaceDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Something in a workspace a user should know about: kept for the bell, and pushed to the
 * user's open tabs. Each kind says what it's about; the app builds the link and actions.
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
        return [WorkspaceDatabaseChannel::class, 'broadcast'];
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
