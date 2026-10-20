<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObject\Products\ProductFiltersData;
use App\Enums\PolicyStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListProducts
{
    /**
     * The agent's catalog, active first, then by name, with how many clients
     * hold each product (cancelled policies left out).
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(User $agent, ProductFiltersData $filters): LengthAwarePaginator
    {
        return $agent->products()
            ->withCount(['policies as policies_count' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)])
            ->when($filters->type, fn (Builder $query, $type) => $query->where('type', $type))
            ->when($filters->active !== null, fn (Builder $query) => $query->where('is_active', $filters->active))
            ->when($filters->search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
