<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\PolicyType;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListCatalogSuggestions
{
    /**
     * Product names typed into policies without a catalog product, grouped ignoring case and
     * spaces, most used first. in_catalog says a product with that name already exists, so the
     * policies only need linking to it.
     *
     * @return Collection<int, array{name: string, type: array{value: string, label: string}, policies: int, commission_rate: float, in_catalog: bool}>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("products:{$workspace->id}:suggestions", function () use ($workspace) {
            $catalog = $workspace->products()->pluck('name')->map(fn (string $name) => mb_strtolower(trim($name)));

            return $workspace->policies()
                ->toBase()
                ->whereNull('product_id')
                ->selectRaw('max(product_name) as name, max(type) as type, count(*) as policies, avg(commission_rate) as commission_rate')
                ->groupByRaw('lower(trim(product_name))')
                ->orderByDesc('policies')
                ->limit(10)
                ->get()
                ->map(fn (object $row) => [
                    'name' => trim($row->name),
                    'type' => ['value' => $row->type, 'label' => PolicyType::from($row->type)->label()],
                    'policies' => (int) $row->policies,
                    'commission_rate' => round((float) $row->commission_rate, 2),
                    'in_catalog' => $catalog->contains(mb_strtolower(trim($row->name))),
                ]);
        });
    }
}
