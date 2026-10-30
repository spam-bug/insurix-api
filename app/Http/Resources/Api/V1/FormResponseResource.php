<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FormResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FormResponse
 */
class FormResponseResource extends JsonResource
{
    /**
     * The answers with the fields they were given to, so they read correctly after the form changes.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->personName(),
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'form' => $this->whenLoaded('form', fn () => ['id' => $this->form->id, 'name' => $this->form->name]),
            'lead' => $this->whenLoaded('lead', fn () => $this->lead ? ['id' => $this->lead->id, 'name' => $this->lead->name] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null),
            'fields' => $this->fields,
            'answers' => (object) $this->answers,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
