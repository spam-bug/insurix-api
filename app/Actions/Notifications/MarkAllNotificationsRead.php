<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarkAllNotificationsRead
{
    /**
     * Marks every unread notification in the workspace read for the user.
     *
     * @return int how many were marked
     */
    public function handle(User $user, Workspace $workspace): int
    {
        $marked = DB::transaction(fn () => $user->unreadNotifications()
            ->where('workspace_id', $workspace->id)
            ->update(['read_at' => now()]));

        Cache::tags("notifications:{$user->id}")->flush();

        return $marked;
    }
}
