<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\SummarizeClients;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ClientSummaryController extends Controller
{
    public function index(Workspace $workspace, SummarizeClients $summarizeClients): JsonResponse
    {
        return response()->json(['data' => $summarizeClients->handle($workspace)]);
    }
}
