<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LogIn;
use App\Actions\Auth\LogOut;
use App\DataTransferObject\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request, LogIn $logIn): UserResource|JsonResponse
    {
        $user = $logIn->handle(LoginData::fromArray([...$request->validated(), 'ip_address' => (string) $request->ip()]));

        return $user ? UserResource::make($user) : response()->json(['two_factor' => true]);
    }

    public function destroy(LogOut $logOut): Response
    {
        $logOut->handle();

        return response()->noContent();
    }
}
