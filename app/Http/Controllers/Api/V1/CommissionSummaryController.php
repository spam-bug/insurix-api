<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\SummarizeCommissions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Commission received, receivable and expected across the agent's policies. */
class CommissionSummaryController extends Controller
{
    public function __invoke(#[CurrentUser] User $agent, SummarizeCommissions $summarizeCommissions): JsonResponse
    {
        return response()->json(['data' => $summarizeCommissions->handle($agent)]);
    }
}
