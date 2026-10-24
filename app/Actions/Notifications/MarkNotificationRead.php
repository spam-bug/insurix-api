<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarkNotificationRead
{
    public function handle(DatabaseNotification $notification): DatabaseNotification
    {
        DB::transaction(fn () => $notification->markAsRead());

        Cache::tags("notifications:{$notification->notifiable_id}")->flush();

        return $notification;
    }
}
