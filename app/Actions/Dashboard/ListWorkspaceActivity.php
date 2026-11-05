<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\LeadActivity;
use App\Models\PolicyEvent;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListWorkspaceActivity
{
    /**
     * What happened lately across the workspace, newest first: lead activity and policy events,
     * each with who did it and the lead or client it's about. Same entry shape as a client's history.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(Workspace $workspace, int $limit = 8): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("dashboard:{$workspace->id}:activity-{$limit}", function () use ($workspace, $limit) {
            $leadActivity = LeadActivity::query()
                ->whereIn('lead_id', $workspace->leads()->select('id'))
                ->with(['user:id,name', 'note:id,lead_activity_id,body', 'lead:id,name'])
                ->latest()
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (LeadActivity $activity) => $this->entry('lead', $activity->id, $activity->type->value, $activity->created_at, $activity->user, [
                    'body' => $activity->note?->body ?? $activity->body,
                    'note_id' => $activity->note?->id,
                    'properties' => $activity->properties ?? (object) [],
                    'lead' => ['id' => $activity->lead->id, 'name' => $activity->lead->name],
                ]));

            $policyEvents = PolicyEvent::query()
                ->whereIn('policy_id', $workspace->policies()->select('id'))
                ->with(['user:id,name', 'policy:id,client_id,product_name', 'policy.client:id,name'])
                ->latest()
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (PolicyEvent $event) => $this->entry('policy', $event->id, $event->type->value, $event->created_at, $event->user, [
                    'properties' => $event->properties ?? (object) [],
                    'policy' => ['id' => $event->policy->id, 'product_name' => $event->policy->product_name],
                    'client' => ['id' => $event->policy->client->id, 'name' => $event->policy->client->name],
                ]));

            return $leadActivity
                ->concat($policyEvents)
                ->sortByDesc(fn (array $entry) => [$entry['created_at'], $entry['id']])
                ->take($limit)
                ->values();
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function entry(string $source, string $id, string $type, mixed $createdAt, mixed $actor, array $extra): array
    {
        return [
            'id' => "{$source}-{$id}",
            'source' => $source,
            'type' => $type,
            ...$extra,
            'actor' => $actor ? ['id' => $actor->id, 'name' => $actor->name] : null,
            'created_at' => $createdAt?->toIso8601String(),
        ];
    }
}
