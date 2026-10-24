<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\MoveApplicationStage;
use App\Actions\Policies\GetPolicy;
use App\Enums\ApplicationStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\MoveApplicationStageRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class ApplicationStageController extends Controller
{
    public function store(MoveApplicationStageRequest $request, Policy $policy, #[CurrentUser] User $user, MoveApplicationStage $moveApplicationStage, GetPolicy $getPolicy): PolicyResource
    {
        $moveApplicationStage->handle($policy, ApplicationStage::from($request->validated('stage')), $user);

        return PolicyResource::make($getPolicy->handle($policy));
    }
}
