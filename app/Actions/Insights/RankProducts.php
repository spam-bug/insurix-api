<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RankProducts
{
    /**
     * "Which products earn me the most?": in-force policies grouped by product, by first-year
     * commission. Policies recorded without a catalog product group by their product name.
     *
     * @return array<int, array{product_id: int|null, name: string, policies: int, annual_premium: float, commission: float}>
     */
    public function handle(User $agent, int $limit = 5): array
    {
        return $agent->policies()
            ->where('status', PolicyStatus::InForce)
            ->get()
            ->groupBy(fn (Policy $policy) => $policy->product_id ? "product:{$policy->product_id}" : 'name:'.Str::lower(trim($policy->product_name)))
            ->map(fn (Collection $policies) => [
                'product_id' => $policies->first()->product_id,
                'name' => $policies->first()->product_name,
                'policies' => $policies->count(),
                'annual_premium' => round($policies->sum(fn (Policy $policy) => $policy->annualPremium()), 2),
                'commission' => round($policies->sum(fn (Policy $policy) => $policy->commission()), 2),
            ])
            ->sortByDesc('commission')
            ->take($limit)
            ->values()
            ->all();
    }
}
