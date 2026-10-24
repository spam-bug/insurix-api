<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\ListPoliciesNeedingAttention;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class PolicyAttentionController extends Controller
{
    /**
     * Policies due a renewal, a premium or a nudge, with why, most urgent first.
     */
    public function index(Workspace $workspace, ListPoliciesNeedingAttention $listPoliciesNeedingAttention): JsonResponse
    {
        return response()->json(['data' => $listPoliciesNeedingAttention->handle($workspace)->map(fn (array $item) => [
            'policy' => PolicyResource::make($item['policy'])->resolve(),
            'reasons' => $item['reasons'],
        ])]);
    }
}
