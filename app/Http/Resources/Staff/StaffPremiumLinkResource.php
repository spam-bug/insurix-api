<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use App\Models\PremiumLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PremiumLink
 */
class StaffPremiumLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $state = $this->state();

        return [
            'id' => $this->id,
            'url' => config('app.frontend_url')."/premium/{$this->token}",
            'days' => $this->days,
            'max_claims' => $this->max_claims,
            'claims' => $this->grants_count,
            'valid_until' => $this->valid_until?->toIso8601String(),
            'email' => $this->email,
            'note' => $this->note,
            'state' => ['value' => $state->value, 'label' => $state->label()],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
