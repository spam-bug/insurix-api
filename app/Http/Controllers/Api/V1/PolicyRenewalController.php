<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\GetPolicy;
use App\Actions\Renewals\RecordRenewalOutcome;
use App\DataTransferObject\Renewals\RenewalData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Renewals\RecordRenewalRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Renewal outcomes the agent records for a policy, answered with the updated policy. */
class PolicyRenewalController extends Controller
{
    public function store(RecordRenewalRequest $request, Policy $policy, #[CurrentUser] User $user, RecordRenewalOutcome $recordRenewalOutcome, GetPolicy $getPolicy): JsonResponse
    {
        $recordRenewalOutcome->handle($policy, RenewalData::fromArray($request->validated()), $user);

        return PolicyResource::make($getPolicy->handle($policy))
            ->response()
            ->setStatusCode(201);
    }
}
