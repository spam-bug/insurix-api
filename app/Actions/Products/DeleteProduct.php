<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class DeleteProduct
{
    /**
     * Only a product no policy uses can be deleted; one that's been sold is deactivated
     * instead, so the clients holding it stay traceable to it.
     *
     * @throws ValidationException
     */
    public function handle(Product $product): void
    {
        if ($product->policies()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Clients hold this product, so it can’t be deleted. Mark it inactive instead.',
            ]);
        }

        $product->delete();
    }
}
