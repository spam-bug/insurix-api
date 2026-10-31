<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
class ClientResource extends JsonResource
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
            'lead_id' => $this->lead_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'preferred_channel' => $this->preferred_channel ? ['value' => $this->preferred_channel->value, 'label' => $this->preferred_channel->label()] : null,
            'messenger_username' => $this->messenger_username,
            'telegram_username' => $this->telegram_username,
            'address' => $this->address,
            'birthdate' => $this->birthdate?->toDateString(),
            'civil_status' => $this->civil_status ? ['value' => $this->civil_status->value, 'label' => $this->civil_status->label()] : null,
            'occupation' => $this->occupation,
            'notes' => $this->notes,
            'in_force_policies' => $this->whenHas('in_force_policies'),
            'annual_premium' => $this->whenHas('annual_premium', fn () => round((float) $this->annual_premium, 2)),
            'next_renewal_on' => $this->whenHas('next_renewal_on', fn () => $this->next_renewal_on?->toDateString()),
            'last_contact_at' => $this->whenHas('last_contact_at', fn () => $this->last_contact_at ? CarbonImmutable::parse($this->last_contact_at)->toIso8601String() : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
