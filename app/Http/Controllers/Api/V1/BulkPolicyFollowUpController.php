<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\BulkCreatePolicyFollowUps;
use App\DataTransferObject\Policies\BulkPolicyFollowUpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\BulkStorePolicyFollowUpRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class BulkPolicyFollowUpController extends Controller
{
    public function store(BulkStorePolicyFollowUpRequest $request, #[CurrentUser] User $agent, BulkCreatePolicyFollowUps $bulkCreatePolicyFollowUps): JsonResponse
    {
        return response()->json($bulkCreatePolicyFollowUps->handle($agent, BulkPolicyFollowUpData::fromArray($request->validated())), 201);
    }
}
