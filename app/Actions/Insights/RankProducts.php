<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Enums\PolicyStatus;
use App\Models\User;

class RankProducts
{
    /**
     * "Which products earn me the most?": in-force policies grouped by product, by first-year
     * commission. Policies recorded without a catalog product group by their product name.
     *
     * @return array<int, array{product_id: string|null, name: string, policies: int, annual_premium: float, commission: float}>
     */
    public function handle(User $agent, int $limit = 5): array
    {
        return $agent->policies()
            ->toBase()
            ->where('status', PolicyStatus::InForce)
            ->selectRaw('max(product_id) as product_id, max(product_name) as name, count(*) as policies, sum(annual_premium) as annual_premium, sum(commission) as commission')
            ->groupByRaw('coalesce(product_id, lower(trim(product_name)))')
            ->orderByDesc('commission')
            ->limit($limit)
            ->get()
            ->map(fn (object $product) => [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'policies' => (int) $product->policies,
                'annual_premium' => round((float) $product->annual_premium, 2),
                'commission' => round((float) $product->commission, 2),
            ])
            ->all();
    }
}
