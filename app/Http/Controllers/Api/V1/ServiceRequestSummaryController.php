<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ServiceRequests\SummarizeServiceRequests;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ServiceRequestSummaryController extends Controller
{
    public function index(Workspace $workspace, SummarizeServiceRequests $summarizeServiceRequests): JsonResponse
    {
        return response()->json(['data' => $summarizeServiceRequests->handle($workspace)]);
    }
}
