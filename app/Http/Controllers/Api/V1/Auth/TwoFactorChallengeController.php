<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\IssueAccessToken;
use App\Actions\Auth\TwoFactorChallenge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\UserResource;

/** The second sign-in step for agents with two-factor on. */
class TwoFactorChallengeController extends Controller
{
    public function __invoke(TwoFactorChallengeRequest $request, TwoFactorChallenge $twoFactorChallenge, IssueAccessToken $issueAccessToken): UserResource
    {
        ['user' => $user, 'device_name' => $deviceName] = $twoFactorChallenge->complete(
            $request->string('challenge')->toString(),
            $request->code(),
            $request->recoveryCode(),
        );

        return UserResource::make($user)->additional(['token' => $issueAccessToken->handle($user, $deviceName)]);
    }
}
