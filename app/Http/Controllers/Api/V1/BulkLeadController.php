<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\BulkDeleteLeads;
use App\DataTransferObject\Leads\LeadSelectionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\BulkDeleteLeadsRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class BulkLeadController extends Controller
{
    public function destroy(BulkDeleteLeadsRequest $request, #[CurrentUser] User $agent, BulkDeleteLeads $bulkDeleteLeads): JsonResponse
    {
        return response()->json(['deleted' => $bulkDeleteLeads->handle($agent, LeadSelectionData::fromArray($request->validated()))]);
    }
}
