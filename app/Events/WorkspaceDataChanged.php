<?php

declare(strict_types=1);

namespace App\Events;

use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Tells the open tabs of a workspace's members that some of its data changed, so the page can
 * re-fetch and point out what's new. Small on purpose: what changed and who did it, never the
 * record's contents. Queued, so a slow or failing broadcaster never holds up the request.
 */
class WorkspaceDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array{id: string, name: string}|null  $actor  null when nobody signed in did it (the form, nightly jobs)
     */
    public function __construct(
        public readonly string $workspaceId,
        public readonly WorkspaceChangeData $change,
        public readonly ?array $actor = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("workspaces.{$this->workspaceId}");
    }

    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    /**
     * @return array{topic: string, action: string, record: array{id: string, name: string|null}|null, count: int, actor: array{id: string, name: string}|null, message: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'topic' => $this->change->topic->value,
            'action' => $this->change->action->value,
            'record' => $this->change->recordId ? ['id' => $this->change->recordId, 'name' => $this->change->recordName] : null,
            'count' => $this->change->count,
            'actor' => $this->actor,
            'message' => $this->change->message,
        ];
    }
}
