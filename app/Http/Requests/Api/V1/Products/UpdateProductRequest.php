<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }
}
