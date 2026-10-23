<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\CountPoliciesByStatus;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** How many of the agent's policies have each status, independent of any list filters. */
class PolicyStatusCountController extends Controller
{
    public function index(Workspace $workspace, CountPoliciesByStatus $countPoliciesByStatus): JsonResponse
    {
        return response()->json(['data' => $countPoliciesByStatus->handle($workspace)]);
    }
}
