<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\SummarizeFollowUps;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class FollowUpSummaryController extends Controller
{
    public function index(Workspace $workspace, SummarizeFollowUps $summarizeFollowUps): JsonResponse
    {
        return response()->json(['data' => $summarizeFollowUps->handle($workspace)]);
    }
}
