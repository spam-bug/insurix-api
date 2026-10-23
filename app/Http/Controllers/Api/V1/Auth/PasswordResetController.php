<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPassword;
use App\Actions\Auth\SendPasswordResetLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

/** "Forgot password?": email a reset link, then set a new password from it. */
class PasswordResetController extends Controller
{
    /**
     * Answers the same whether or not the email has an account.
     */
    public function sendLink(ForgotPasswordRequest $request, SendPasswordResetLink $sendPasswordResetLink): JsonResponse
    {
        $sendPasswordResetLink->handle($request->string('email')->trim()->lower()->toString());

        return response()->json(['message' => 'If that email has an account, a reset link is on its way.']);
    }

    public function reset(ResetPasswordRequest $request, ResetPassword $resetPassword): JsonResponse
    {
        $resetPassword->handle(
            $request->string('email')->trim()->lower()->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json(['message' => 'Your password has been reset. Log in with your new password.']);
    }
}
