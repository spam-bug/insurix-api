<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\BulkCreatePolicyFollowUps;
use App\DataTransferObject\Policies\BulkPolicyFollowUpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\BulkStorePolicyFollowUpRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class BulkPolicyFollowUpController extends Controller
{
    public function store(BulkStorePolicyFollowUpRequest $request, Workspace $workspace, BulkCreatePolicyFollowUps $bulkCreatePolicyFollowUps): JsonResponse
    {
        return response()->json($bulkCreatePolicyFollowUps->handle($workspace, BulkPolicyFollowUpData::fromArray($request->validated())), 201);
    }
}
