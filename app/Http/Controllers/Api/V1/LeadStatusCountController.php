<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\CountLeadsByStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** How many of the agent's leads sit in each pipeline stage, independent of any list filters. */
class LeadStatusCountController extends Controller
{
    public function __invoke(#[CurrentUser] User $agent, CountLeadsByStatus $countLeadsByStatus): JsonResponse
    {
        return response()->json(['data' => $countLeadsByStatus->handle($agent)]);
    }
}
