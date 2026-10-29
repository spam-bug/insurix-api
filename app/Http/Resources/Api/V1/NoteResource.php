<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Note
 */
class NoteResource extends JsonResource
{
    /** What toArray() reads; load these first. */
    public const RELATIONS = ['user:id,name,avatar_path', 'lead:id,name', 'client:id,name', 'policy:id,product_name'];

    /**
     * The link is the note's most specific record: its policy, else its client, else its lead.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name, 'avatar_url' => $this->user->avatarUrl()] : null,
            'link' => match (true) {
                $this->policy !== null => ['kind' => 'policy', 'id' => $this->policy->id, 'name' => $this->policy->product_name, 'client' => $this->client?->name],
                $this->client !== null => ['kind' => 'client', 'id' => $this->client->id, 'name' => $this->client->name],
                $this->lead !== null => ['kind' => 'lead', 'id' => $this->lead->id, 'name' => $this->lead->name],
                default => null,
            },
            'pinned_at' => $this->pinned_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
