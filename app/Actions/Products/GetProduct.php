<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class GetProduct
{
    /**
     * The product with its figures (see ProductBuilder::withFigures).
     */
    public function handle(Product $product): Product
    {
        return Cache::tags("workspace:{$product->workspace_id}")->rememberForever(
            "products:{$product->workspace_id}:{$product->id}",
            fn () => Product::query()->whereKey($product->id)->withFigures()->firstOrFail(),
        );
    }
}
