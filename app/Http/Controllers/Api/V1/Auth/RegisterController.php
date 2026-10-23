<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\IssueAccessToken;
use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser, IssueAccessToken $issueAccessToken): JsonResponse
    {
        $user = $registerUser->handle($request->toData());

        return UserResource::make($user)
            ->additional(['token' => $issueAccessToken->handle($user, $request->string('device_name')->toString())])
            ->response()
            ->setStatusCode(201);
    }
}
