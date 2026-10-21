<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\GetProduct;
use App\Actions\Products\ListProducts;
use App\Actions\Products\UpdateProduct;
use App\DataTransferObject\Products\ProductData;
use App\DataTransferObject\Products\ProductFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\ListProductsRequest;
use App\Http\Requests\Api\V1\Products\StoreProductRequest;
use App\Http\Requests\Api\V1\Products\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request, #[CurrentUser] User $agent, ListProducts $listProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($listProducts->handle($agent, ProductFiltersData::fromArray($request->validated())));
    }

    public function store(StoreProductRequest $request, #[CurrentUser] User $agent, CreateProduct $createProduct): JsonResponse
    {
        return ProductResource::make($createProduct->handle($agent, ProductData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product, GetProduct $getProduct): ProductResource
    {
        return ProductResource::make($getProduct->handle($product));
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $updateProduct): ProductResource
    {
        return ProductResource::make($updateProduct->handle($product, ProductData::fromArray($request->validated())));
    }

    public function destroy(Product $product, DeleteProduct $deleteProduct): Response
    {
        $deleteProduct->handle($product);

        return response()->noContent();
    }
}
