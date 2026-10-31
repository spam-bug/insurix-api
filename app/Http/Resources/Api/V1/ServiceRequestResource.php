<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceRequest
 */
class ServiceRequestResource extends JsonResource
{
    /** What toArray() reads; load these first. */
    public const RELATIONS = ['client:id,name', 'policy:id,product_name', 'user:id,name'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'title' => $this->title,
            'details' => $this->details,
            'due_on' => $this->due_on?->toDateString(),
            'claim_amount' => $this->claim_amount !== null ? (float) $this->claim_amount : null,
            'insurer_reference' => $this->insurer_reference,
            'client' => ['id' => $this->client->id, 'name' => $this->client->name],
            'policy' => $this->policy ? ['id' => $this->policy->id, 'product_name' => $this->policy->product_name] : null,
            'author' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
