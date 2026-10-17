<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Policy
 */
class PolicyResource extends JsonResource
{
    /**
     * Transform the resource into an array. Amounts are pesos as numbers; dates are YYYY-MM-DD.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]),
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'policy_number' => $this->policy_number,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'premium' => (float) $this->premium,
            'coverage_amount' => $this->coverage_amount === null ? null : (float) $this->coverage_amount,
            'payment_frequency' => ['value' => $this->payment_frequency->value, 'label' => $this->payment_frequency->label()],
            'annual_premium' => $this->annualPremium(),
            'commission_rate' => (float) $this->commission_rate,
            'commission' => $this->commission(),
            'effective_date' => $this->effective_date?->toDateString(),
            'renewal_date' => $this->renewal_date?->toDateString(),
            'next_payment_due' => $this->nextPaymentDue()?->toDateString(),
            'commission_received_at' => $this->commission_received_at?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
