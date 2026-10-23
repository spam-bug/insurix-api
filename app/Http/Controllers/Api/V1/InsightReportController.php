<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Insights\BuildInsightReport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class InsightReportController extends Controller
{
    public function index(#[CurrentUser] User $agent, BuildInsightReport $buildInsightReport): JsonResponse
    {
        return response()->json(['data' => $buildInsightReport->handle($agent)]);
    }
}
