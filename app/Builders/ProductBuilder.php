<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @extends Builder<Product>
 */
class ProductBuilder extends Builder
{
    /**
     * What the catalog compares products by: how many clients hold it (cancelled left out), its
     * policies, annual premium and first-year commission in force, and when it was last sold.
     */
    public function withFigures(): static
    {
        $inForce = fn (Builder|HasMany $query) => $query->where('status', PolicyStatus::InForce);

        return $this
            ->withCount(['policies as policies_count' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)])
            ->withCount(['policies as in_force_policies' => $inForce])
            ->withSum(['policies as annual_premium' => $inForce], 'annual_premium')
            ->withSum(['policies as commission' => $inForce], 'commission')
            ->withMax(['policies as last_sold_at' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)], 'created_at');
    }

    /** Largest first-year commission in force first; products without any last. */
    public function orderByCommission(): static
    {
        return $this->orderByDesc(
            Policy::query()->selectRaw('coalesce(sum(commission), 0)')->whereColumn('product_id', 'products.id')->where('status', PolicyStatus::InForce),
        );
    }
}
