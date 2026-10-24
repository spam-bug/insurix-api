<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class CountUnreadNotifications
{
    /** The number on the bell. Cached until a notification arrives or is read. */
    public function handle(User $user, Workspace $workspace): int
    {
        return Cache::tags("notifications:{$user->id}")->rememberForever(
            "notifications:{$user->id}:{$workspace->id}:unread-count",
            fn () => $user->unreadNotifications()->where('workspace_id', $workspace->id)->count(),
        );
    }
}
