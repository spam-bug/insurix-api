<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\VerifyEmailAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    public function store(VerifyEmailRequest $request, User $user, string $hash, VerifyEmailAddress $verifyEmailAddress): Response
    {
        $verifyEmailAddress->handle($user, $hash);

        return response()->noContent();
    }
}
