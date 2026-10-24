<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\BulkUpdateLeadStatus;
use App\DataTransferObject\Leads\BulkLeadStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\BulkUpdateLeadStatusRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class BulkLeadStatusController extends Controller
{
    public function update(BulkUpdateLeadStatusRequest $request, Workspace $workspace, #[CurrentUser] User $user, BulkUpdateLeadStatus $bulkUpdateLeadStatus): JsonResponse
    {
        return response()->json(['updated' => $bulkUpdateLeadStatus->handle($workspace, BulkLeadStatusData::fromArray($request->validated()), $user)]);
    }
}
