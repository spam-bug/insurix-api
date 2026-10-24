<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Insights\BuildInsightReport;
use App\DataTransferObject\Insights\InsightReportFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insights\ShowInsightReportRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class InsightReportController extends Controller
{
    public function index(ShowInsightReportRequest $request, Workspace $workspace, BuildInsightReport $buildInsightReport): JsonResponse
    {
        return response()->json(['data' => $buildInsightReport->handle($workspace, InsightReportFiltersData::fromArray($request->validated()))]);
    }
}
