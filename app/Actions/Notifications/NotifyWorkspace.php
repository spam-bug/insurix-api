<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Events\WorkspaceDataChanged;
use Illuminate\Support\Facades\DB;

class NotifyWorkspace
{
    /**
     * Pushes a change to the open tabs of everyone in the workspace once the current transaction commits. Live
     * updates are a nicety: if Pusher can't be reached the error is reported and the request
     * carries on, so a form submission never fails because of it.
     *
     * @param  'leads'|'follow-ups'  $topic
     */
    public function handle(string $workspaceId, string $topic, ?string $message = null): void
    {
        DB::afterCommit(fn () => rescue(fn () => WorkspaceDataChanged::dispatch($workspaceId, $topic, $message)));
    }
}
