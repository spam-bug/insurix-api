<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payments\DeletePolicyPayment;
use App\Actions\Payments\RecordPolicyPayment;
use App\Actions\Payments\UpdatePolicyPayment;
use App\Actions\Policies\GetPolicy;
use App\DataTransferObject\Payments\PolicyPaymentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\StorePolicyPaymentRequest;
use App\Http\Requests\Api\V1\Payments\UpdatePolicyPaymentRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/** Premiums recorded as paid on a policy, answered with the updated policy. */
class PolicyPaymentController extends Controller
{
    public function store(StorePolicyPaymentRequest $request, Policy $policy, #[CurrentUser] User $user, RecordPolicyPayment $recordPolicyPayment, GetPolicy $getPolicy): JsonResponse
    {
        $recordPolicyPayment->handle($policy, PolicyPaymentData::fromArray($request->validated()), $user);

        return PolicyResource::make($getPolicy->handle($policy))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePolicyPaymentRequest $request, PolicyPayment $payment, #[CurrentUser] User $user, UpdatePolicyPayment $updatePolicyPayment, GetPolicy $getPolicy): PolicyResource
    {
        $updatePolicyPayment->handle($payment, PolicyPaymentData::fromArray($request->validated()), $user);

        return PolicyResource::make($getPolicy->handle($payment->policy));
    }

    public function destroy(PolicyPayment $payment, DeletePolicyPayment $deletePolicyPayment): Response
    {
        $deletePolicyPayment->handle($payment);

        return response()->noContent();
    }
}
