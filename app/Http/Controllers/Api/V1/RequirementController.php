<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\DeleteRequirement;
use App\Actions\Applications\UpdateRequirement;
use App\Actions\Policies\GetPolicy;
use App\DataTransferObject\Applications\RequirementData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\UpdateRequirementRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\ApplicationRequirement;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class RequirementController extends Controller
{
    public function update(UpdateRequirementRequest $request, ApplicationRequirement $requirement, #[CurrentUser] User $agent, UpdateRequirement $updateRequirement, GetPolicy $getPolicy): PolicyResource
    {
        $updateRequirement->handle($requirement, RequirementData::fromArray($request->validated()), $agent);

        return PolicyResource::make($getPolicy->handle($requirement->policy));
    }

    public function destroy(ApplicationRequirement $requirement, DeleteRequirement $deleteRequirement): Response
    {
        $deleteRequirement->handle($requirement);

        return response()->noContent();
    }
}
