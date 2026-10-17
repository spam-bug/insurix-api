<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\ListProducts;
use App\Actions\Products\UpdateProduct;
use App\Enums\PolicyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\ListProductsRequest;
use App\Http\Requests\Api\V1\Products\StoreProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** The agent's product catalog: the insurers' products they sell. */
class ProductController extends Controller
{
    public function index(ListProductsRequest $request, #[CurrentUser] User $agent, ListProducts $listProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($listProducts->handle($agent, $request->toData()));
    }

    public function store(StoreProductRequest $request, #[CurrentUser] User $agent, CreateProduct $createProduct): JsonResponse
    {
        return ProductResource::make($createProduct->handle($agent, $request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return ProductResource::make($product->loadCount(['policies as policies_count' => fn (Builder $query) => $query->where('status', '!=', PolicyStatus::Cancelled)]));
    }

    public function update(StoreProductRequest $request, Product $product, UpdateProduct $updateProduct): ProductResource
    {
        Gate::authorize('update', $product);

        return ProductResource::make($updateProduct->handle($product, $request->toData()));
    }

    public function destroy(Product $product, DeleteProduct $deleteProduct): Response
    {
        Gate::authorize('delete', $product);

        $deleteProduct->handle($product);

        return response()->noContent();
    }
}
