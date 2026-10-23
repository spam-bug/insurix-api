<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPassword;
use App\DataTransferObject\Auth\PasswordResetData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function store(ResetPasswordRequest $request, ResetPassword $resetPassword): JsonResponse
    {
        $resetPassword->handle(PasswordResetData::fromArray($request->validated()));

        return response()->json(['message' => 'Your password has been reset. Log in with your new password.']);
    }
}
