<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\BulkDeleteLeads;
use App\DataTransferObject\Leads\LeadSelectionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\BulkDeleteLeadsRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class BulkLeadController extends Controller
{
    public function destroy(BulkDeleteLeadsRequest $request, Workspace $workspace, BulkDeleteLeads $bulkDeleteLeads): JsonResponse
    {
        return response()->json(['deleted' => $bulkDeleteLeads->handle($workspace, LeadSelectionData::fromArray($request->validated()))]);
    }
}
