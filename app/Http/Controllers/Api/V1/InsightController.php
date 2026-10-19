<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Insights\BuildInsightReport;
use App\Actions\Insights\SuggestNextActions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Reports and suggestions that answer "what should I do next to grow my business?". */
class InsightController extends Controller
{
    public function reports(#[CurrentUser] User $agent, BuildInsightReport $buildInsightReport): JsonResponse
    {
        return response()->json(['data' => $buildInsightReport->handle($agent)]);
    }

    public function nextActions(#[CurrentUser] User $agent, SuggestNextActions $suggestNextActions): JsonResponse
    {
        return response()->json(['data' => $suggestNextActions->handle($agent)]);
    }
}
