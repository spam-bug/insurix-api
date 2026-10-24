<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'description' => $this->description,
            'commission_rate' => (float) $this->commission_rate,
            'payment_frequency' => $this->payment_frequency ? ['value' => $this->payment_frequency->value, 'label' => $this->payment_frequency->label()] : null,
            'is_active' => $this->is_active,
            'policies_count' => $this->whenCounted('policies_count'),
            'in_force_policies' => $this->whenHas('in_force_policies'),
            'annual_premium' => $this->whenHas('annual_premium', fn () => round((float) $this->annual_premium, 2)),
            'commission' => $this->whenHas('commission', fn () => round((float) $this->commission, 2)),
            'last_sold_at' => $this->whenHas('last_sold_at', fn () => $this->last_sold_at ? CarbonImmutable::parse($this->last_sold_at)->toIso8601String() : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
