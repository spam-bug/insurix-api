<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Actions\Mentions\RecordMentions;
use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Policy;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchWorkspace
{
    /** How many matches each kind of record shows. */
    public const PER_KIND = 5;

    /**
     * A few of the best matches of each kind, newest first: leads not yet promoted (a promoted
     * lead is found as the client), clients, policies by number, product or client, documents
     * by name, and notes by their text.
     *
     * @return array<string, array<int, array{id: string, title: string, subtitle: string|null}>>
     */
    public function handle(Workspace $workspace, string $search): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("search:{$workspace->id}:".md5(Str::lower($search)), now()->addMinutes(5), function () use ($workspace, $search) {
            $like = "%{$search}%";
            $person = fn (Builder $query) => $query->whereLike('name', $like)->orWhereLike('email', $like)->orWhereLike('phone', $like);

            return [
                'leads' => $workspace->leads()
                    ->whereDoesntHave('client')
                    ->where($person)
                    ->latest()
                    ->limit(self::PER_KIND)
                    ->get(['id', 'name', 'email', 'phone'])
                    ->map(fn (Lead $lead) => $this->result($lead->id, $lead->name, $lead->email ?? $lead->phone))
                    ->all(),
                'clients' => $workspace->clients()
                    ->where($person)
                    ->latest()
                    ->limit(self::PER_KIND)
                    ->get(['id', 'name', 'email', 'phone'])
                    ->map(fn (Client $client) => $this->result($client->id, $client->name, $client->email ?? $client->phone))
                    ->all(),
                'policies' => $workspace->policies()
                    ->where(fn (Builder $query) => $query
                        ->whereLike('policy_number', $like)
                        ->orWhereLike('product_name', $like)
                        ->orWhereHas('client', fn (Builder $client) => $client->whereLike('name', $like)))
                    ->with('client:id,name')
                    ->latest()
                    ->limit(self::PER_KIND)
                    ->get(['id', 'client_id', 'product_name', 'policy_number'])
                    ->map(fn (Policy $policy) => $this->result($policy->id, $policy->product_name, collect([$policy->client->name, $policy->policy_number])->filter()->implode(' · ')))
                    ->all(),
                'documents' => $workspace->documents()
                    ->whereLike('name', $like)
                    ->with('client:id,name')
                    ->latest()
                    ->limit(self::PER_KIND)
                    ->get(['id', 'client_id', 'name'])
                    ->map(fn (Document $document) => $this->result($document->id, $document->name, $document->client?->name, ['client_id' => $document->client_id]))
                    ->all(),
                'notes' => $workspace->notes()
                    ->whereLike('body', $like)
                    ->with(['lead:id,name', 'client:id,name'])
                    ->latest()
                    ->limit(self::PER_KIND)
                    ->get(['id', 'lead_id', 'client_id', 'body'])
                    ->map(fn (Note $note) => $this->result($note->id, Str::limit(preg_replace(RecordMentions::PATTERN, '@$1', $note->body), 80), $note->client?->name ?? $note->lead?->name))
                    ->all(),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{id: string, title: string, subtitle: string|null}
     */
    private function result(string $id, string $title, ?string $subtitle, array $extra = []): array
    {
        return ['id' => $id, 'title' => $title, 'subtitle' => $subtitle, ...$extra];
    }
}
