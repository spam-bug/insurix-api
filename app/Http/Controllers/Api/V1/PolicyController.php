<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\CreatePolicy;
use App\Actions\Policies\DeletePolicy;
use App\Actions\Policies\ListPolicies;
use App\Actions\Policies\ListPoliciesNeedingAttention;
use App\Actions\Policies\UpdatePolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\ListPoliciesRequest;
use App\Http\Requests\Api\V1\Policies\StorePolicyRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PolicyController extends Controller
{
    public function index(ListPoliciesRequest $request, #[CurrentUser] User $agent, ListPolicies $listPolicies): AnonymousResourceCollection
    {
        return PolicyResource::collection($listPolicies->handle($agent, $request->toData()));
    }

    /**
     * Policies due a renewal, a premium or a nudge, with why, most urgent first.
     */
    public function attention(#[CurrentUser] User $agent, ListPoliciesNeedingAttention $listPoliciesNeedingAttention): JsonResponse
    {
        $items = $listPoliciesNeedingAttention->handle($agent)->map(fn (array $item) => [
            'policy' => PolicyResource::make($item['policy'])->resolve(),
            'reasons' => $item['reasons'],
        ]);

        return response()->json(['data' => $items]);
    }

    public function store(StorePolicyRequest $request, Client $client, CreatePolicy $createPolicy): JsonResponse
    {
        Gate::authorize('update', $client);

        return PolicyResource::make($createPolicy->handle($client, $request->toData())->load('client:id,name'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Policy $policy): PolicyResource
    {
        Gate::authorize('view', $policy);

        return PolicyResource::make($policy->load('client:id,name'));
    }

    public function update(StorePolicyRequest $request, Policy $policy, UpdatePolicy $updatePolicy): PolicyResource
    {
        Gate::authorize('update', $policy);

        return PolicyResource::make($updatePolicy->handle($policy, $request->toData())->load('client:id,name'));
    }

    public function destroy(Policy $policy, DeletePolicy $deletePolicy): Response
    {
        Gate::authorize('delete', $policy);

        $deletePolicy->handle($policy);

        return response()->noContent();
    }
}
