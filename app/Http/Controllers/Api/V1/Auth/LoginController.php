<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\IssueAccessToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, AuthenticateUser $authenticateUser, IssueAccessToken $issueAccessToken): UserResource
    {
        $user = $authenticateUser->handle($request->toData());

        return UserResource::make($user)
            ->additional(['token' => $issueAccessToken->handle($user, $request->string('device_name')->toString())]);
    }
}
