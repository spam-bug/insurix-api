<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\BulkDeleteLeads;
use App\Actions\Leads\BulkUpdateLeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\BulkLeadRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class BulkLeadController extends Controller
{
    public function updateStatus(BulkLeadRequest $request, #[CurrentUser] User $agent, BulkUpdateLeadStatus $bulkUpdateLeadStatus): JsonResponse
    {
        return response()->json(['updated' => $bulkUpdateLeadStatus->handle($agent, $request->leadIds(), $request->status())]);
    }

    public function destroy(BulkLeadRequest $request, #[CurrentUser] User $agent, BulkDeleteLeads $bulkDeleteLeads): JsonResponse
    {
        return response()->json(['deleted' => $bulkDeleteLeads->handle($agent, $request->leadIds())]);
    }
}
