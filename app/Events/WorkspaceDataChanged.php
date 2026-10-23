<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Tells an agent's open tabs that some of their data changed without them doing it, so the
 * page can re-fetch. Queued, so a slow or failing broadcaster never holds up the request.
 */
class WorkspaceDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  'leads'|'follow-ups'  $topic  what changed
     * @param  string|null  $message  a short note worth showing the workspace's members, if any
     */
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $topic,
        public readonly ?string $message = null,
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
     * @return array{topic: string, message: string|null}
     */
    public function broadcastWith(): array
    {
        return ['topic' => $this->topic, 'message' => $this->message];
    }
}
