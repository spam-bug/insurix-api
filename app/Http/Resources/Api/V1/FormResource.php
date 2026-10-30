<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Form
 */
class FormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'token' => $this->token,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'is_default' => $this->is_default,
            'template' => $this->template?->value,
            'draft' => $this->draft,
            'published' => $this->published,
            'has_unpublished_changes' => $this->draft !== $this->published,
            'responses_count' => $this->whenCounted('responses'),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
