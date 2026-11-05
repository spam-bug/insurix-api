<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\ListWorkspaceActivity;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** The latest lead activity and policy events across the workspace, for the dashboard. */
class WorkspaceActivityController extends Controller
{
    public function index(Workspace $workspace, ListWorkspaceActivity $listWorkspaceActivity): JsonResponse
    {
        return response()->json(['data' => $listWorkspaceActivity->handle($workspace)]);
    }
}
