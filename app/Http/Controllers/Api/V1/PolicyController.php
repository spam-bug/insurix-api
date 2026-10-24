<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\DeletePolicy;
use App\Actions\Policies\GetPolicy;
use App\Actions\Policies\ListPolicies;
use App\Actions\Policies\UpdatePolicy;
use App\DataTransferObject\Policies\PolicyData;
use App\DataTransferObject\Policies\PolicyFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\ListPoliciesRequest;
use App\Http\Requests\Api\V1\Policies\UpdatePolicyRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\Workspace;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PolicyController extends Controller
{
    public function index(ListPoliciesRequest $request, Workspace $workspace, ListPolicies $listPolicies): AnonymousResourceCollection
    {
        return PolicyResource::collection($listPolicies->handle($workspace, PolicyFiltersData::fromArray($request->validated())));
    }

    public function show(Policy $policy, GetPolicy $getPolicy): PolicyResource
    {
        return PolicyResource::make($getPolicy->handle($policy));
    }

    public function update(UpdatePolicyRequest $request, Policy $policy, UpdatePolicy $updatePolicy): PolicyResource
    {
        return PolicyResource::make($updatePolicy->handle($policy, PolicyData::fromArray($request->validated()))->load('client:id,name,lead_id'));
    }

    public function destroy(Policy $policy, DeletePolicy $deletePolicy): Response
    {
        $deletePolicy->handle($policy);

        return response()->noContent();
    }
}
