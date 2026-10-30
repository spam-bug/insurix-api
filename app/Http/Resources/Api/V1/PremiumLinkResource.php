<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PremiumLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the claim page shows. The email is never sent; only whether the link is for one account.
 *
 * @mixin PremiumLink
 */
class PremiumLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'days' => $this->days,
            'state' => ['value' => $this->state()->value, 'label' => $this->state()->label()],
            'for_one_account' => $this->email !== null,
            'valid_until' => $this->valid_until?->toIso8601String(),
        ];
    }
}
