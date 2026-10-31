<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Actions\Leads\ScoreLead;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lead
 */
class LeadResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'preferred_channel' => $this->preferred_channel ? ['value' => $this->preferred_channel->value, 'label' => $this->preferred_channel->label()] : null,
            'messenger_username' => $this->messenger_username,
            'telegram_username' => $this->telegram_username,
            'address' => $this->address,
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'interest' => $this->interest ? ['value' => $this->interest->value, 'label' => $this->interest->label()] : null,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'notes' => $this->notes,
            'meeting_at' => $this->meeting_at?->toIso8601String(),
            'client_id' => $this->client?->id,
            'score' => $this->score,
            'score_band' => match (true) {
                $this->score === null => null,
                $this->score >= ScoreLead::HOT_SCORE => 'hot',
                $this->score >= ScoreLead::WARM_SCORE => 'warm',
                default => 'cold',
            },
            'score_reasons' => $this->score_reasons ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
