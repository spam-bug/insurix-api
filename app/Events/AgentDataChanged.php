<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Tells an agent's open tabs that some of their data changed without them doing it, so the
 * page can re-fetch. Broadcast right away rather than queued, so no queue worker is needed.
 */
class AgentDataChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  'leads'|'follow-ups'  $topic  what changed
     * @param  string|null  $message  a short note worth showing the agent, if any
     */
    public function __construct(
        public readonly string $agentId,
        public readonly string $topic,
        public readonly ?string $message = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("agents.{$this->agentId}");
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
