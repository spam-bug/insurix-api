<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\ChartDashboardTrends;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** New leads and commission received over the last six months, for the dashboard's sparklines. */
class DashboardTrendController extends Controller
{
    public function index(Workspace $workspace, ChartDashboardTrends $chartDashboardTrends): JsonResponse
    {
        return response()->json(['data' => $chartDashboardTrends->handle($workspace)]);
    }
}
