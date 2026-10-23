<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\SummarizeFollowUps;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class FollowUpSummaryController extends Controller
{
    public function index(#[CurrentUser] User $agent, SummarizeFollowUps $summarizeFollowUps): JsonResponse
    {
        return response()->json(['data' => $summarizeFollowUps->handle($agent)]);
    }
}
