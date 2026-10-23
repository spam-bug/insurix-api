<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\IssueAccessToken;
use App\Actions\Auth\TwoFactorChallenge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Agents with two-factor on get a challenge to answer with a code instead of a token.
     */
    public function __invoke(LoginRequest $request, AuthenticateUser $authenticateUser, IssueAccessToken $issueAccessToken, TwoFactorChallenge $twoFactorChallenge): UserResource|JsonResponse
    {
        $user = $authenticateUser->handle($request->toData());
        $deviceName = $request->string('device_name')->toString();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['two_factor' => true, 'challenge' => $twoFactorChallenge->start($user, $deviceName)]);
        }

        return UserResource::make($user)->additional(['token' => $issueAccessToken->handle($user, $deviceName)]);
    }
}
