<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Search\SearchWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Search\SearchRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function index(SearchRequest $request, Workspace $workspace, SearchWorkspace $searchWorkspace): JsonResponse
    {
        return response()->json(['data' => $searchWorkspace->handle($workspace, $request->validated('q'))]);
    }
}
