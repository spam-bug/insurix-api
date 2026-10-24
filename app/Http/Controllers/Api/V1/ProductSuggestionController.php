<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Products\AddSuggestedProduct;
use App\Actions\Products\ListCatalogSuggestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\AddSuggestedProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** Product names typed into policies, and adding one to the catalog with its policies linked. */
class ProductSuggestionController extends Controller
{
    public function index(Workspace $workspace, ListCatalogSuggestions $listCatalogSuggestions): JsonResponse
    {
        return response()->json(['data' => $listCatalogSuggestions->handle($workspace)]);
    }

    public function store(AddSuggestedProductRequest $request, Workspace $workspace, AddSuggestedProduct $addSuggestedProduct): JsonResponse
    {
        ['product' => $product, 'linked' => $linked] = $addSuggestedProduct->handle($workspace, $request->validated('name'));

        return ProductResource::make($product)->additional(['linked' => $linked])->response()->setStatusCode(201);
    }
}
