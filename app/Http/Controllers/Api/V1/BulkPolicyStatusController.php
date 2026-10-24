<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\BulkUpdatePolicyStatus;
use App\DataTransferObject\Policies\BulkPolicyStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\BulkUpdatePolicyStatusRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class BulkPolicyStatusController extends Controller
{
    public function update(BulkUpdatePolicyStatusRequest $request, Workspace $workspace, BulkUpdatePolicyStatus $bulkUpdatePolicyStatus): JsonResponse
    {
        return response()->json(['updated' => $bulkUpdatePolicyStatus->handle($workspace, BulkPolicyStatusData::fromArray($request->validated()))]);
    }
}
