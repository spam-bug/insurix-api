<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\CompleteTwoFactorChallenge;
use App\DataTransferObject\Auth\TwoFactorChallengeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\UserResource;

class TwoFactorChallengeController extends Controller
{
    public function store(TwoFactorChallengeRequest $request, CompleteTwoFactorChallenge $completeTwoFactorChallenge): UserResource
    {
        return UserResource::make($completeTwoFactorChallenge->handle(TwoFactorChallengeData::fromArray($request->validated())));
    }
}
