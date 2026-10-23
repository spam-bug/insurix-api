<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\PolicyData;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

class PreparePolicyAttributes
{
    /**
     * A picked catalog product sets the product name and type. An inactive product can't be
     * picked, though a policy already on it keeps it. Only a custom term keeps a fixed renewal
     * date; set terms are worked out from the effective date.
     *
     * @return array<string, mixed>
     */
    public function handle(Workspace $workspace, PolicyData $data, ?Policy $current = null): array
    {
        $product = $data->productId === null ? null : $workspace->products()->findOrFail($data->productId);

        if ($product && ! $product->is_active && $current?->product_id !== $product->id) {
            throw ValidationException::withMessages(['product_id' => 'This product is inactive. Reactivate it in your catalog to use it.']);
        }

        return [
            ...$data->toArray(),
            'product_name' => $product->name ?? $data->productName,
            'type' => $product->type ?? $data->type ?? PolicyType::Other,
            'renewal_date' => $data->renewalTerm === RenewalTerm::Custom ? $data->renewalDate : null,
        ];
    }
}
