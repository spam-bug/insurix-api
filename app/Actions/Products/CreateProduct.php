<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObject\Products\ProductData;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function handle(Workspace $workspace, ProductData $data): Product
    {
        $product = DB::transaction(fn () => $workspace->products()->create($data->toArray()));

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $product;
    }
}
