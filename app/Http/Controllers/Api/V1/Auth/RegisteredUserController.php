<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUser;
use App\DataTransferObject\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        return UserResource::make($registerUser->handle(RegisterUserData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }
}
