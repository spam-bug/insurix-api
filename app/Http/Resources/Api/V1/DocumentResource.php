<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array. The file itself is fetched from download_path
     * with the agent's token; there's no public URL.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy
                ? ['id' => $this->policy->id, 'product_name' => $this->policy->product_name, 'policy_number' => $this->policy->policy_number]
                : null),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'expires_on' => $this->expires_on?->toDateString(),
            'expiry_status' => $this->expiryStatus(),
            'notes' => $this->notes,
            'download_path' => "/documents/{$this->id}/download",
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
