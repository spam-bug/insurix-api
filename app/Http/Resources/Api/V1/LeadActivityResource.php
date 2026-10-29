<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\LeadActivityType;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeadActivity
 */
class LeadActivityResource extends JsonResource
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
            'type' => $this->type->value,
            // A note's text lives on the note, so edits show here too.
            'body' => $this->type === LeadActivityType::Note ? $this->note?->body ?? $this->body : $this->body,
            'note_id' => $this->type === LeadActivityType::Note ? $this->note?->id : null,
            'properties' => $this->properties ?? (object) [],
            'actor' => $this->whenLoaded('user', fn () => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
