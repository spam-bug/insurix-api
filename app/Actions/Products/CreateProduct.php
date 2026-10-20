<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObjects\Products\ProductData;
use App\Models\Product;
use App\Models\User;

class CreateProduct
{
    public function handle(User $agent, ProductData $data): Product
    {
        return $agent->products()->create($data->toAttributes());
    }
}
