<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\ListNextUpLeads;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** The few leads that need the agent now, each with why ("overdue", "hot_quiet" or "form_reply"). */
class NextUpLeadController extends Controller
{
    public function index(Workspace $workspace, ListNextUpLeads $listNextUpLeads): JsonResponse
    {
        return response()->json(['data' => $listNextUpLeads->handle($workspace)->map(fn (array $item) => [
            'reason' => $item['reason'],
            'lead' => LeadResource::make($item['lead']),
        ])]);
    }
}
