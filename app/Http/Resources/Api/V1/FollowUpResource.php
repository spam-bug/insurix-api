<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FollowUp
 */
class FollowUpResource extends JsonResource
{
    /** What the resource shows about the person and policy; load these wherever it's returned. */
    public const RELATIONS = [
        'lead:id,name,phone,email,preferred_channel,messenger_username,telegram_username',
        'client:id,name,phone,email,preferred_channel,messenger_username,telegram_username',
        'policy:id,product_name',
    ];

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
            'message' => $this->message,
            'due_at' => $this->due_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'lead' => $this->whenLoaded('lead', fn () => $this->lead ? $this->contact($this->lead) : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? $this->contact($this->client) : null),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy?->only('id', 'product_name')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * The person and every way to reach them, so the follow-up can be acted on in their channel.
     *
     * @return array<string, mixed>
     */
    private function contact(Lead|Client $person): array
    {
        return [
            ...$person->only('id', 'name', 'phone', 'email', 'messenger_username', 'telegram_username'),
            'preferred_channel' => $person->preferred_channel ? ['value' => $person->preferred_channel->value, 'label' => $person->preferred_channel->label()] : null,
        ];
    }
}
