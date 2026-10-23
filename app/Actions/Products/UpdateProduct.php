<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObjects\Products\ProductData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    /**
     * Changes the product for future policies. Policies already recorded keep the product
     * name and commission rate they were saved with.
     */
    public function handle(Product $product, ProductData $data): Product
    {
        DB::transaction(fn () => $product->update($data->toAttributes()));

        return $product;
    }
}
