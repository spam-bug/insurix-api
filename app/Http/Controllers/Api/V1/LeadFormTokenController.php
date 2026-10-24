<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\RegenerateLeadFormToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class LeadFormTokenController extends Controller
{
    public function store(ManageWorkspaceRequest $request, Workspace $workspace, RegenerateLeadFormToken $regenerateLeadFormToken): JsonResponse
    {
        return response()->json(['data' => ['token' => $regenerateLeadFormToken->handle($workspace)]]);
    }
}
