<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FollowUp
 */
class FollowUpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'title' => $this->title,
            'notes' => $this->notes,
            'due_at' => $this->due_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'lead' => $this->whenLoaded('lead', fn () => $this->lead ? ['id' => $this->lead->id, 'name' => $this->lead->name] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
