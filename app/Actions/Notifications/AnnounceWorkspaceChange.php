<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Events\WorkspaceDataChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnnounceWorkspaceChange
{
    /**
     * Every write ends here: the workspace's cached reads are dropped, and once the transaction
     * commits, the open tabs of everyone else in the workspace hear what changed and who did it.
     * The person who made the change isn't told (their browser sends its socket id). Live
     * updates are a nicety: if Pusher can't be reached the error is reported and the request
     * carries on.
     */
    public function handle(string $workspaceId, WorkspaceChangeData $change): void
    {
        Cache::tags("workspace:{$workspaceId}")->flush();

        $actor = Auth::user();
        $event = new WorkspaceDataChanged($workspaceId, $change, $actor ? ['id' => $actor->id, 'name' => $actor->name] : null);

        DB::afterCommit(fn () => rescue(fn () => broadcast($event)->toOthers()));
    }
}
