<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\MeasureUsage;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class WorkspaceUsageController extends Controller
{
    public function index(Workspace $workspace, MeasureUsage $measureUsage): JsonResponse
    {
        return response()->json(['data' => $measureUsage->handle($workspace)]);
    }
}
