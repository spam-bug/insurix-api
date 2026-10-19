<?php

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\Document;
use App\Models\LeadActivity;
use App\Models\PolicyEvent;
use Illuminate\Support\Collection;

class ListClientHistory
{
    /**
     * Everything that happened with the client, newest first, as one list: the activity of
     * the lead they were promoted from, the timeline of each of their policies, and the
     * documents uploaded for them. Documents count from their upload; a deleted one drops out.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(Client $client, int $limit = 50): Collection
    {
        $leadActivity = $client->lead
            ? $client->lead->activities()->with('user:id,name')->latest()->latest('id')->limit($limit)->get()
                ->map(fn (LeadActivity $activity) => $this->entry('lead', $activity->id, $activity->type->value, $activity->created_at, $activity->user, [
                    'body' => $activity->body,
                    'properties' => $activity->properties ?? (object) [],
                ]))
            : collect();

        $policyEvents = PolicyEvent::query()
            ->whereIn('policy_id', $client->policies()->select('id'))
            ->with(['user:id,name', 'policy:id,product_name'])
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (PolicyEvent $event) => $this->entry('policy', $event->id, $event->type->value, $event->created_at, $event->user, [
                'properties' => $event->properties ?? (object) [],
                'policy' => ['id' => $event->policy->id, 'product_name' => $event->policy->product_name],
            ]));

        $documents = $client->documents()
            ->with(['user:id,name', 'policy:id,product_name'])
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Document $document) => $this->entry('document', $document->id, 'uploaded', $document->created_at, $document->user, [
                'document' => ['id' => $document->id, 'name' => $document->name, 'category' => $document->category->label()],
                'policy' => $document->policy ? ['id' => $document->policy->id, 'product_name' => $document->policy->product_name] : null,
            ]));

        return $leadActivity
            ->concat($policyEvents)
            ->concat($documents)
            ->sortByDesc(fn (array $entry) => [$entry['created_at'], $entry['id']])
            ->take($limit)
            ->values();
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
