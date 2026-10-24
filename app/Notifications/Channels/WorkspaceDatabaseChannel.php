<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\WorkspaceNotification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/** Laravel's database channel, plus the workspace each notification belongs to. */
class WorkspaceDatabaseChannel extends DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $stored = parent::send($notifiable, $notification);

        Cache::tags("notifications:{$notifiable->id}")->flush();

        return $stored;
    }

    /**
     * @param  WorkspaceNotification  $notification
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        return [...parent::buildPayload($notifiable, $notification), 'workspace_id' => $notification->workspaceId];
    }
}
