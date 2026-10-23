<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Insights\SuggestNextActions;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class NextActionController extends Controller
{
    public function index(Workspace $workspace, SuggestNextActions $suggestNextActions): JsonResponse
    {
        return response()->json(['data' => $suggestNextActions->handle($workspace)]);
    }
}
