<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Renewals\RecordRenewalOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Renewals\RecordRenewalRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Renewal outcomes the agent records for a policy, answered with the updated policy. */
class PolicyRenewalController extends Controller
{
    public function store(RecordRenewalRequest $request, Policy $policy, #[CurrentUser] User $agent, RecordRenewalOutcome $recordRenewalOutcome): JsonResponse
    {
        Gate::authorize('update', $policy);

        $recordRenewalOutcome->handle($policy, $request->dueOn(), $request->outcome(), $request->nextRenewalDate(), $request->notes(), $agent);

        return PolicyResource::make($policy->fresh()->load(PolicyResource::DETAIL_RELATIONS))
            ->response()
            ->setStatusCode(201);
    }
}
