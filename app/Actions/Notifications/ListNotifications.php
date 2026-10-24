<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\DataTransferObject\Notifications\NotificationFiltersData;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListNotifications
{
    public const PER_PAGE = 20;

    /**
     * The user's bell notifications for one workspace, newest first, optionally only unread or
     * of one kind. Cached until a notification arrives or is read.
     */
    public function handle(User $user, Workspace $workspace, NotificationFiltersData $filters): LengthAwarePaginator
    {
        $key = "notifications:{$user->id}:{$workspace->id}:".($filters->unread ? 'unread' : 'all').":{$filters->type?->value}:page-{$filters->page}";

        return Cache::tags("notifications:{$user->id}")->rememberForever($key, fn () => $user->notifications()
            ->where('workspace_id', $workspace->id)
            ->when($filters->unread, fn ($query) => $query->whereNull('read_at'))
            ->when($filters->type, fn ($query) => $query->where('type', $filters->type->value))
            ->paginate(self::PER_PAGE, page: $filters->page));
    }
}
