<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObject\Products\ProductFiltersData;
use App\Enums\ProductSort;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListProducts
{
    /**
     * The workspace's catalog with each product's figures (see ProductBuilder::withFigures),
     * sorted by name (active first), by policies in force, or by commission in force.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(Workspace $workspace, ProductFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("products:{$workspace->id}:index:".md5(json_encode($filters)), function () use ($workspace, $filters) {
            $query = $workspace->products()
                ->withFigures()
                ->when($filters->type, fn (Builder $query, $type) => $query->where('type', $type))
                ->when($filters->active !== null, fn (Builder $query) => $query->where('is_active', $filters->active))
                ->when($filters->search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"));

            match ($filters->sort) {
                ProductSort::Name => $query->orderByDesc('is_active')->orderBy('name'),
                ProductSort::MostSold => $query->orderByDesc('in_force_policies')->orderBy('name'),
                ProductSort::TopEarning => $query->orderByCommission()->orderBy('name'),
            };

            return $query->paginate($filters->perPage, page: $filters->page)->withQueryString();
        });
    }
}
