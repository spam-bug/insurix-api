<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\PolicyStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class GetProduct
{
    /**
     * The product with how many clients hold it (cancelled policies left out).
     */
    public function handle(Product $product): Product
    {
        return Cache::tags("workspace:{$product->workspace_id}")->rememberForever("products:{$product->workspace_id}:{$product->id}", fn () => $product->loadCount(['policies as policies_count' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)]));
    }
}
