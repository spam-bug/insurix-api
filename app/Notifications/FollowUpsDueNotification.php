<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** The morning reminder: bell, email, and a push notification on each device that turned them on. */
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

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable);

        return $channels !== [] && $notifiable->pushSubscriptions()->exists() ? [...$channels, WebPushChannel::class] : $channels;
    }

    /** Tapping it opens the workspace's Follow-ups; the tag replaces yesterday's if it's still showing. */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        ['title' => $title, 'body' => $body] = $this->toArray($notifiable);

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->tag('follow-ups-due')
            ->data(['url' => "/w/{$this->workspaceId}/follow-ups"]);
    }
}
