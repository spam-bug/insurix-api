<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\PolicyStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class GetProduct
{
    /**
     * The product with how many clients hold it (cancelled policies left out).
     */
    public function handle(Product $product): Product
    {
        return $product->loadCount(['policies as policies_count' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)]);
    }
}
