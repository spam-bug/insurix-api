<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\SummarizePremiumByType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** The agent's annual premium in force, split by policy type. */
class PolicyPremiumMixController extends Controller
{
    public function index(#[CurrentUser] User $agent, SummarizePremiumByType $summarizePremiumByType): JsonResponse
    {
        return response()->json(['data' => $summarizePremiumByType->handle($agent)]);
    }
}
