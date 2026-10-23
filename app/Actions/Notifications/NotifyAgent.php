<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Events\AgentDataChanged;
use Illuminate\Support\Facades\DB;

class NotifyAgent
{
    /**
     * Pushes a change to the agent's open tabs once the current transaction commits. Live
     * updates are a nicety: if Pusher can't be reached the error is reported and the request
     * carries on, so a form submission never fails because of it.
     *
     * @param  'leads'|'follow-ups'  $topic
     */
    public function handle(string $agentId, string $topic, ?string $message = null): void
    {
        DB::afterCommit(fn () => rescue(fn () => AgentDataChanged::dispatch($agentId, $topic, $message)));
    }
}
