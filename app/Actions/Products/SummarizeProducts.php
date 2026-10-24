<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\PolicyStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class SummarizeProducts
{
    /**
     * The catalog at a glance: active and inactive products, the product earning the most
     * first-year commission in force, and how many policies (cancelled left out) were typed in
     * without a catalog product.
     *
     * @return array{active: int, inactive: int, top_earner: array{id: string, name: string, commission: float}|null, off_catalog_policies: int}
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("products:{$workspace->id}:summary", function () use ($workspace) {
            $counts = $workspace->products()->toBase()->selectRaw('is_active, count(*) as aggregate')->groupBy('is_active')->pluck('aggregate', 'is_active');
            $topEarner = $workspace->products()->withSum(['policies as commission' => fn ($query) => $query->where('status', PolicyStatus::InForce)], 'commission')
                ->orderByCommission()
                ->first();

            return [
                'active' => (int) ($counts[1] ?? 0),
                'inactive' => (int) ($counts[0] ?? 0),
                'top_earner' => $topEarner && (float) $topEarner->commission > 0
                    ? ['id' => $topEarner->id, 'name' => $topEarner->name, 'commission' => round((float) $topEarner->commission, 2)]
                    : null,
                'off_catalog_policies' => $workspace->policies()->whereNull('product_id')->where('status', '!=', PolicyStatus::Cancelled)->count(),
            ];
        });
    }
}
