<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceDeletedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeleteWorkspace
{
    /**
     * Locks and hides the workspace at once (soft delete). The owner can restore it for
     * Workspace::DELETION_GRACE_DAYS; then PurgeWorkspace removes its data and files. The other
     * members hear about it by email.
     */
    public function handle(Workspace $workspace, User $owner): void
    {
        $others = $workspace->users()->whereKeyNot($owner->id)->get();

        DB::transaction(fn () => $workspace->delete());

        Cache::tags("workspace:{$workspace->id}")->flush();

        Notification::send($others, new WorkspaceDeletedNotification(
            $workspace->name,
            $owner->name,
            $workspace->deleted_at->copy()->addDays(Workspace::DELETION_GRACE_DAYS),
        ));
    }
}
