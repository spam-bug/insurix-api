<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /** The relations the client and policy links need. */
    public const RELATIONS = ['client:id,name', 'policy:id,product_name,policy_number'];

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
            'client' => $this->whenLoaded('client', fn () => $this->client?->only('id', 'name')),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy
                ? ['id' => $this->policy->id, 'product_name' => $this->policy->product_name, 'policy_number' => $this->policy->policy_number]
                : null),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'notes' => $this->notes,
            'download_path' => "/documents/{$this->id}/download",
            // Browsers can show these inline (download_path?inline=1); HEIC and Office files they can't.
            'previewable' => in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
