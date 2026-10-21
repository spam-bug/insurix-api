<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObject\Products\ProductData;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function handle(User $agent, ProductData $data): Product
    {
        $product = DB::transaction(fn () => $agent->products()->create($data->toArray()));

        Cache::tags("agent:{$agent->id}")->flush();

        return $product;
    }
}
