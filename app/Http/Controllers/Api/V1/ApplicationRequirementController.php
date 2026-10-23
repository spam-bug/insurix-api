<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\AddRequirement;
use App\Actions\Policies\GetPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\StoreRequirementRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class ApplicationRequirementController extends Controller
{
    public function store(StoreRequirementRequest $request, Policy $policy, #[CurrentUser] User $agent, AddRequirement $addRequirement, GetPolicy $getPolicy): PolicyResource
    {
        $addRequirement->handle($policy, $request->validated('title'), $agent);

        return PolicyResource::make($getPolicy->handle($policy));
    }
}
