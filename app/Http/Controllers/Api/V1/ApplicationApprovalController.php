<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\ApproveApplication;
use App\Actions\Policies\GetPolicy;
use App\DataTransferObject\Applications\ApprovalData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\ApproveApplicationRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class ApplicationApprovalController extends Controller
{
    public function store(ApproveApplicationRequest $request, Policy $policy, #[CurrentUser] User $user, ApproveApplication $approveApplication, GetPolicy $getPolicy): PolicyResource
    {
        $approveApplication->handle($policy, ApprovalData::fromArray($request->validated()), $user);

        return PolicyResource::make($getPolicy->handle($policy));
    }
}
