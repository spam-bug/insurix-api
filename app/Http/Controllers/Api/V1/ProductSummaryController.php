<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Products\SummarizeProducts;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ProductSummaryController extends Controller
{
    public function index(Workspace $workspace, SummarizeProducts $summarizeProducts): JsonResponse
    {
        return response()->json(['data' => $summarizeProducts->handle($workspace)]);
    }
}
