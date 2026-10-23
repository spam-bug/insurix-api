<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\DeclineApplication;
use App\Actions\Policies\GetPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\DeclineApplicationRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class ApplicationDeclineController extends Controller
{
    public function store(DeclineApplicationRequest $request, Policy $policy, #[CurrentUser] User $agent, DeclineApplication $declineApplication, GetPolicy $getPolicy): PolicyResource
    {
        $declineApplication->handle($policy, $request->validated('reason'), $agent);

        return PolicyResource::make($getPolicy->handle($policy));
    }
}
