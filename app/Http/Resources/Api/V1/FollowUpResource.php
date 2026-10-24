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
    /** What the resource shows about the person and policy; load these wherever it's returned. */
    public const RELATIONS = ['lead:id,name,phone,email', 'client:id,name,phone,email', 'policy:id,product_name'];

    /**
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
            'lead' => $this->whenLoaded('lead', fn () => $this->lead?->only('id', 'name', 'phone', 'email')),
            'client' => $this->whenLoaded('client', fn () => $this->client?->only('id', 'name', 'phone', 'email')),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy?->only('id', 'product_name')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
